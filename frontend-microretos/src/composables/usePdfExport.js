import { jsPDF } from 'jspdf';

const GREEN  = [0, 168, 89];
const DARK   = [31, 41, 55];
const GRAY   = [107, 114, 128];
const LGRAY  = [75, 85, 99];
const YELLOW = [161, 128, 0];
const RED    = [220, 38, 38];
const BLUE   = [37, 99, 235];

const PAGE_W   = 210;
const PAGE_H   = 297;
const MARGIN   = 14;
const CONTENT_W = PAGE_W - MARGIN * 2;

export function usePdfExport() {
  const descargarPDF = (reto) => {
    const doc = new jsPDF({ unit: 'mm', format: 'a4', compress: true });
    let y = 0;

    // ── helpers ─────────────────────────────────────────────────────────

    const checkBreak = (needed = 20) => {
      if (y + needed > PAGE_H - 12) {
        doc.addPage();
        y = 12;
      }
    };

    const setFont = (size, style = 'normal', color = LGRAY) => {
      doc.setFontSize(size);
      doc.setFont('helvetica', style);
      doc.setTextColor(...color);
    };

    const addSectionTitle = (text, color = DARK) => {
      checkBreak(12);
      setFont(6.5, 'bold', color);
      doc.text(text.toUpperCase(), MARGIN, y);
      doc.setDrawColor(...color);
      doc.setLineWidth(0.25);
      doc.line(MARGIN, y + 1, MARGIN + CONTENT_W, y + 1);
      y += 7;
    };

    const addParagraph = (text, maxW = CONTENT_W, indent = 0) => {
      if (!text) return;
      setFont(8.5, 'normal', LGRAY);
      const lines = doc.splitTextToSize(String(text), maxW);
      checkBreak(lines.length * 4.5 + 3);
      doc.text(lines, MARGIN + indent, y);
      y += lines.length * 4.5 + 3;
    };

    const addBulletList = (items, bulletColor = GREEN) => {
      if (!items?.length) return;
      items.forEach(item => {
        const lines = doc.splitTextToSize(String(item), CONTENT_W - 6);
        checkBreak(lines.length * 4.5 + 2);
        setFont(9, 'bold', bulletColor);
        doc.text('•', MARGIN, y);
        setFont(8.5, 'normal', LGRAY);
        doc.text(lines, MARGIN + 5, y);
        y += lines.length * 4.5 + 1.5;
      });
      y += 2;
    };

    const drawBadge = (text, bgColor, txtColor, x, badgeY) => {
      setFont(6.5, 'bold', txtColor);
      const tw = doc.getTextWidth(text);
      const bw = tw + 5;
      doc.setFillColor(...bgColor);
      doc.roundedRect(x, badgeY - 3.5, bw, 6, 1.5, 1.5, 'F');
      doc.text(text, x + 2.5, badgeY);
      return bw + 2.5;
    };

    // ── HEADER BAND ──────────────────────────────────────────────────────

    // Green top band
    doc.setFillColor(...GREEN);
    doc.rect(0, 0, PAGE_W, 8, 'F');

    // Label
    setFont(6, 'bold', [255, 255, 255]);
    doc.text('DUALAB · FICHA DE MICRORETO', MARGIN, 5.5);

    y = 13;

    // Title
    setFont(18, 'bold', DARK);
    const titleLines = doc.splitTextToSize(reto.titulo || 'Sin título', CONTENT_W);
    doc.text(titleLines, MARGIN, y);
    y += titleLines.length * 7 + 1;

    // Subtitle
    if (reto.subtitulo) {
      setFont(9.5, 'normal', GRAY);
      const subLines = doc.splitTextToSize(reto.subtitulo, CONTENT_W);
      doc.text(subLines, MARGIN, y);
      y += subLines.length * 4.8 + 2;
    }

    y += 3;

    // Badges row
    let bx = MARGIN;
    if (reto.empresa_nombre) bx += drawBadge(reto.empresa_nombre, DARK, [255, 255, 255], bx, y);
    if (reto.familia)        bx += drawBadge(reto.familia,        [229,231,235], DARK,           bx, y);
    if (reto.ciclo)          bx += drawBadge(reto.ciclo,          [209,250,229], GREEN,          bx, y);
    if (reto.nivel_grupo)    bx += drawBadge(`Nivel ${reto.nivel_grupo}`, [243,244,246], GRAY,   bx, y);
    if (reto.centro_educativo || reto.centro)
      drawBadge(reto.centro_educativo || reto.centro, [243,244,246], GRAY, bx, y);

    y += 10;

    // Separator
    doc.setDrawColor(229, 231, 235);
    doc.setLineWidth(0.3);
    doc.line(MARGIN, y, MARGIN + CONTENT_W, y);
    y += 6;

    // ── QUIÉN ES / DÍA A DÍA ─────────────────────────────────────────────

    if (reto.quien_es) {
      addSectionTitle(`¿Quién es ${reto.empresa_nombre || 'la empresa'}?`, GREEN);
      addParagraph(reto.quien_es);
    }

    if (reto.dia_a_dia) {
      addSectionTitle('Su día a día', GREEN);
      addParagraph(reto.dia_a_dia);
    }

    // ── DIFICULTADES ──────────────────────────────────────────────────────

    if (reto.dificultades?.length) {
      addSectionTitle('Dificultades', YELLOW);
      addBulletList(reto.dificultades, YELLOW);
    }

    // ── PREGUNTA DEL RETO ─────────────────────────────────────────────────

    if (reto.pregunta_reto) {
      const pregLines = doc.splitTextToSize(reto.pregunta_reto, CONTENT_W - 10);
      const boxH = pregLines.length * 5.2 + 12;
      checkBreak(boxH + 6);

      doc.setFillColor(240, 253, 244);
      doc.roundedRect(MARGIN, y, CONTENT_W, boxH, 2, 2, 'F');
      doc.setFillColor(...GREEN);
      doc.rect(MARGIN, y, 2.5, boxH, 'F');

      setFont(6.5, 'bold', GREEN);
      doc.text('PREGUNTA DEL RETO', MARGIN + 6, y + 5.5);

      setFont(10, 'bold', DARK);
      doc.text(pregLines, MARGIN + 6, y + 10.5);

      y += boxH + 7;
    }

    // ── QUÉ NECESITAN / LIMITACIONES ─────────────────────────────────────

    if (reto.que_necesitan?.length) {
      addSectionTitle('Qué necesitan', GREEN);
      addBulletList(reto.que_necesitan, GREEN);
    }

    if (reto.limitaciones?.length) {
      addSectionTitle('Limitaciones', RED);
      addBulletList(reto.limitaciones, RED);
    }

    // ── PROTOTIPOS / ODS ──────────────────────────────────────────────────

    if (reto.prototipos?.length) {
      addSectionTitle('Ejemplos de Prototipos', GREEN);
      addBulletList(reto.prototipos, GREEN);
    }

    if (reto.ods_sugeridos?.length) {
      addSectionTitle('ODS Sugeridos', BLUE);
      reto.ods_sugeridos.forEach(ods => {
        checkBreak(6);
        setFont(8.5, 'bold', DARK);
        doc.text(String(ods), MARGIN + 2, y);
        y += 5;
      });
      y += 3;
    }

    // ── RA / CE ──────────────────────────────────────────────────────────

    if (reto.evaluacion_oficial?.length) {
      addSectionTitle('RA/CE Seleccionados', DARK);

      reto.evaluacion_oficial.forEach(evalObj => {
        const raLines  = doc.splitTextToSize(evalObj.ra || '', CONTENT_W - 10);
        const ceLines  = (evalObj.ce || []).map(ce => doc.splitTextToSize(String(ce), CONTENT_W - 14));
        const aplLines = evalObj.aplicacion ? doc.splitTextToSize(evalObj.aplicacion, CONTENT_W - 10) : [];
        const modLines = doc.splitTextToSize(evalObj.modulo || '', CONTENT_W - 10);

        const totalCeH = ceLines.reduce((s, l) => s + l.length * 4.5 + 1.5, 0);
        const boxH = 7 + modLines.length * 5 + 8 + raLines.length * 4.5 + 8
          + totalCeH + (aplLines.length ? aplLines.length * 4.5 + 7 : 0) + 4;

        checkBreak(Math.min(boxH, 60));

        doc.setFillColor(249, 250, 251);
        doc.setDrawColor(229, 231, 235);
        doc.setLineWidth(0.25);
        doc.roundedRect(MARGIN, y, CONTENT_W, boxH, 2, 2, 'FD');

        let iy = y + 5;

        setFont(6, 'bold', GRAY);
        doc.text('MÓDULO', MARGIN + 4, iy);
        iy += 4;
        setFont(9, 'bold', DARK);
        doc.text(modLines, MARGIN + 4, iy);
        iy += modLines.length * 5 + 3;

        setFont(6, 'bold', GREEN);
        doc.text('RESULTADO DE APRENDIZAJE', MARGIN + 4, iy);
        iy += 4;
        setFont(8.5, 'normal', LGRAY);
        doc.text(raLines, MARGIN + 4, iy);
        iy += raLines.length * 4.5 + 4;

        setFont(6, 'bold', GRAY);
        doc.text('CRITERIOS DE EVALUACIÓN', MARGIN + 4, iy);
        iy += 4;
        ceLines.forEach(ceL => {
          setFont(7, 'bold', GREEN);
          doc.text('✓', MARGIN + 4, iy);
          setFont(8, 'normal', LGRAY);
          doc.text(ceL, MARGIN + 9, iy);
          iy += ceL.length * 4.5 + 1.5;
        });

        if (aplLines.length) {
          iy += 2;
          setFont(7.5, 'bold', DARK);
          doc.text('Aplicación: ', MARGIN + 4, iy);
          const aplX = MARGIN + 4 + doc.getTextWidth('Aplicación: ');
          setFont(7.5, 'italic', LGRAY);
          doc.text(aplLines, aplX, iy);
        }

        y += boxH + 5;
      });
    }

    // ── VARIANTES ────────────────────────────────────────────────────────

    if (reto.variantes?.length) {
      addSectionTitle('Variantes', GREEN);
      reto.variantes.forEach(varItem => {
        const hasColon = varItem.includes(':');
        const label    = hasColon ? varItem.split(':')[0] : null;
        const body     = hasColon ? varItem.substring(varItem.indexOf(':') + 1).trim() : varItem;
        const bodyLines = doc.splitTextToSize(body, CONTENT_W - 8);
        const boxH = bodyLines.length * 4.5 + (label ? 12 : 8);

        checkBreak(boxH + 4);
        doc.setFillColor(249, 250, 251);
        doc.setDrawColor(229, 231, 235);
        doc.setLineWidth(0.25);
        doc.roundedRect(MARGIN, y, CONTENT_W, boxH, 2, 2, 'FD');

        let vy = y + 5;
        if (label) {
          setFont(8, 'bold', DARK);
          doc.text(label, MARGIN + 4, vy);
          vy += 5.5;
        }
        setFont(8.5, 'normal', LGRAY);
        doc.text(bodyLines, MARGIN + 4, vy);
        y += boxH + 4;
      });
      y += 2;
    }

    // ── TIPS PROFESORADO ─────────────────────────────────────────────────

    if (reto.tips_profesorado?.length) {
      checkBreak(14);
      setFont(6.5, 'bold', YELLOW);
      doc.text('USO EXCLUSIVO DOCENTE', MARGIN, y);
      y += 5;
      addSectionTitle('Guía de Implementación', YELLOW);

      reto.tips_profesorado.forEach(tip => {
        const hasColon = tip.includes(':');
        const label    = hasColon ? tip.split(':')[0] : null;
        const body     = hasColon ? tip.substring(tip.indexOf(':') + 1).trim() : tip;
        const bodyLines = doc.splitTextToSize(body, CONTENT_W - 8);
        const boxH = bodyLines.length * 4.5 + (label ? 12 : 8);

        checkBreak(boxH + 4);
        doc.setFillColor(255, 255, 255);
        doc.setDrawColor(229, 231, 235);
        doc.setLineWidth(0.25);
        doc.roundedRect(MARGIN, y, CONTENT_W, boxH, 2, 2, 'FD');

        let ty = y + 5;
        if (label) {
          setFont(7.5, 'bold', GREEN);
          doc.text(label.toUpperCase(), MARGIN + 4, ty);
          ty += 5.5;
        }
        setFont(8.5, 'normal', LGRAY);
        doc.text(bodyLines, MARGIN + 4, ty);
        y += boxH + 4;
      });
    }

    // ── FOOTER en cada página ────────────────────────────────────────────

    const pageCount = doc.internal.getNumberOfPages();
    const today = new Date().toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
    for (let i = 1; i <= pageCount; i++) {
      doc.setPage(i);
      setFont(6.5, 'normal', [156, 163, 175]);
      doc.text(
        `DuaLab · Generado el ${today} · Página ${i} de ${pageCount}`,
        MARGIN,
        PAGE_H - 6
      );
      doc.setDrawColor(229, 231, 235);
      doc.setLineWidth(0.2);
      doc.line(MARGIN, PAGE_H - 9, MARGIN + CONTENT_W, PAGE_H - 9);
    }

    // ── SAVE ─────────────────────────────────────────────────────────────

    const slug = (reto.titulo || 'microreto')
      .toLowerCase()
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .replace(/\s+/g, '-')
      .replace(/[^a-z0-9-]/g, '');

    doc.save(`microreto-${slug}.pdf`);
  };

  return { descargarPDF };
}
