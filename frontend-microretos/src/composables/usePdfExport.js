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

// ── Utilidades de módulo ──────────────────────────────────────────────────────

function slugify(text) {
  return (text || 'documento')
    .toLowerCase()
    .normalize('NFD').replace(/[̀-ͯ]/g, '')
    .replace(/\s+/g, '-')
    .replace(/[^a-z0-9-]/g, '');
}

// Añade pie de página con número de página y fecha en todas las páginas del doc
function addFooters(doc) {
  const pageCount = doc.internal.getNumberOfPages();
  const today = new Date().toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
  for (let i = 1; i <= pageCount; i++) {
    doc.setPage(i);
    doc.setFontSize(6.5);
    doc.setFont('helvetica', 'normal');
    doc.setTextColor(156, 163, 175);
    doc.text(
      `DuaLab · Generado el ${today} · Página ${i} de ${pageCount}`,
      MARGIN,
      PAGE_H - 6
    );
    doc.setDrawColor(229, 231, 235);
    doc.setLineWidth(0.2);
    doc.line(MARGIN, PAGE_H - 9, MARGIN + CONTENT_W, PAGE_H - 9);
  }
}

// ── Factoría de renderer ──────────────────────────────────────────────────────
// Devuelve renderReto y renderCoverPage, que comparten el mismo doc y el estado
// mutable s.y para controlar la posición vertical actual en la página.

function makeRenderer(doc) {
  const s = { y: 0 };

  // ── helpers ─────────────────────────────────────────────────────────

  const checkBreak = (needed = 20) => {
    if (s.y + needed > PAGE_H - 12) {
      doc.addPage();
      s.y = 12;
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
    doc.text(text.toUpperCase(), MARGIN, s.y);
    doc.setDrawColor(...color);
    doc.setLineWidth(0.25);
    doc.line(MARGIN, s.y + 1, MARGIN + CONTENT_W, s.y + 1);
    s.y += 7;
  };

  const addParagraph = (text, maxW = CONTENT_W, indent = 0) => {
    if (!text) return;
    setFont(8.5, 'normal', LGRAY);
    const lines = doc.splitTextToSize(String(text), maxW);
    checkBreak(lines.length * 4.5 + 3);
    doc.text(lines, MARGIN + indent, s.y);
    s.y += lines.length * 4.5 + 3;
  };

  const addBulletList = (items, bulletColor = GREEN) => {
    if (!items?.length) return;
    items.forEach(item => {
      const lines = doc.splitTextToSize(String(item), CONTENT_W - 6);
      checkBreak(lines.length * 4.5 + 2);
      setFont(9, 'bold', bulletColor);
      doc.text('•', MARGIN, s.y);
      setFont(8.5, 'normal', LGRAY);
      doc.text(lines, MARGIN + 5, s.y);
      s.y += lines.length * 4.5 + 1.5;
    });
    s.y += 2;
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

  // ── Renderiza un microreto desde el inicio de la página actual ─────────────
  const renderReto = (reto) => {
    s.y = 0;

    // ── HEADER BAND ──────────────────────────────────────────────────────

    // Banda verde superior
    doc.setFillColor(...GREEN);
    doc.rect(0, 0, PAGE_W, 8, 'F');

    // Label
    setFont(6, 'bold', [255, 255, 255]);
    doc.text('DUALAB · FICHA DE MICRORETO', MARGIN, 5.5);

    s.y = 13;

    // Title
    setFont(18, 'bold', DARK);
    const titleLines = doc.splitTextToSize(reto.titulo || 'Sin título', CONTENT_W);
    doc.text(titleLines, MARGIN, s.y);
    s.y += titleLines.length * 7 + 1;

    // Subtitle
    if (reto.subtitulo) {
      setFont(9.5, 'normal', GRAY);
      const subLines = doc.splitTextToSize(reto.subtitulo, CONTENT_W);
      doc.text(subLines, MARGIN, s.y);
      s.y += subLines.length * 4.8 + 2;
    }

    s.y += 3;

    // Badges row
    let bx = MARGIN;
    if (reto.empresa_nombre) bx += drawBadge(reto.empresa_nombre, DARK, [255, 255, 255], bx, s.y);
    if (reto.familia)        bx += drawBadge(reto.familia,        [229,231,235], DARK,           bx, s.y);
    if (reto.ciclo)          bx += drawBadge(reto.ciclo,          [209,250,229], GREEN,          bx, s.y);
    if (reto.nivel_grupo)    bx += drawBadge(`Nivel ${reto.nivel_grupo}`, [243,244,246], GRAY,   bx, s.y);
    if (reto.centro_educativo || reto.centro)
      drawBadge(reto.centro_educativo || reto.centro, [243,244,246], GRAY, bx, s.y);

    s.y += 10;

    // Separator
    doc.setDrawColor(229, 231, 235);
    doc.setLineWidth(0.3);
    doc.line(MARGIN, s.y, MARGIN + CONTENT_W, s.y);
    s.y += 6;

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
      doc.roundedRect(MARGIN, s.y, CONTENT_W, boxH, 2, 2, 'F');
      doc.setFillColor(...GREEN);
      doc.rect(MARGIN, s.y, 2.5, boxH, 'F');

      setFont(6.5, 'bold', GREEN);
      doc.text('PREGUNTA DEL RETO', MARGIN + 6, s.y + 5.5);

      setFont(10, 'bold', DARK);
      doc.text(pregLines, MARGIN + 6, s.y + 10.5);

      s.y += boxH + 7;
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
        doc.text(String(ods), MARGIN + 2, s.y);
        s.y += 5;
      });
      s.y += 3;
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
        doc.roundedRect(MARGIN, s.y, CONTENT_W, boxH, 2, 2, 'FD');

        let iy = s.y + 5;

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

        s.y += boxH + 5;
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
        doc.roundedRect(MARGIN, s.y, CONTENT_W, boxH, 2, 2, 'FD');

        let vy = s.y + 5;
        if (label) {
          setFont(8, 'bold', DARK);
          doc.text(label, MARGIN + 4, vy);
          vy += 5.5;
        }
        setFont(8.5, 'normal', LGRAY);
        doc.text(bodyLines, MARGIN + 4, vy);
        s.y += boxH + 4;
      });
      s.y += 2;
    }

    // ── TIPS PROFESORADO ─────────────────────────────────────────────────

    if (reto.tips_profesorado?.length) {
      checkBreak(14);
      setFont(6.5, 'bold', YELLOW);
      doc.text('USO EXCLUSIVO DOCENTE', MARGIN, s.y);
      s.y += 5;
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
        doc.roundedRect(MARGIN, s.y, CONTENT_W, boxH, 2, 2, 'FD');

        let ty = s.y + 5;
        if (label) {
          setFont(7.5, 'bold', GREEN);
          doc.text(label.toUpperCase(), MARGIN + 4, ty);
          ty += 5.5;
        }
        setFont(8.5, 'normal', LGRAY);
        doc.text(bodyLines, MARGIN + 4, ty);
        s.y += boxH + 4;
      });
    }
  };

  // ── Portada del PDF de grupo: índice con estadísticas y listado de retos ───
  const renderCoverPage = (retos, titulo, subtitulo) => {
    s.y = 0;

    // Banda verde superior
    doc.setFillColor(...GREEN);
    doc.rect(0, 0, PAGE_W, 8, 'F');
    setFont(6, 'bold', [255, 255, 255]);
    doc.text('DUALAB · BIBLIOTECA DE MICRORETOS', MARGIN, 5.5);

    s.y = 22;

    // Título principal del grupo
    setFont(22, 'bold', DARK);
    const titleLines = doc.splitTextToSize(titulo, CONTENT_W);
    doc.text(titleLines, MARGIN, s.y);
    s.y += titleLines.length * 9 + 3;

    // Subtítulo (centro, filtros aplicados, etc.)
    if (subtitulo) {
      setFont(10, 'normal', GRAY);
      const subLines = doc.splitTextToSize(subtitulo, CONTENT_W);
      doc.text(subLines, MARGIN, s.y);
      s.y += subLines.length * 5.5 + 4;
    }

    s.y += 6;

    // Cajas de estadísticas: Total / Bajo / Medio / Alto
    const stats = {
      total: retos.length,
      Bajo:  retos.filter(r => r.nivel_grupo === 'Bajo').length,
      Medio: retos.filter(r => r.nivel_grupo === 'Medio').length,
      Alto:  retos.filter(r => r.nivel_grupo === 'Alto').length,
    };
    const statsItems = [
      { label: 'Total',  value: stats.total, color: DARK   },
      { label: 'Bajo',   value: stats.Bajo,  color: GREEN  },
      { label: 'Medio',  value: stats.Medio, color: YELLOW },
      { label: 'Alto',   value: stats.Alto,  color: RED    },
    ];
    const boxW = (CONTENT_W - 3 * 3) / 4;
    statsItems.forEach((item, i) => {
      const bx = MARGIN + i * (boxW + 3);
      doc.setFillColor(249, 250, 251);
      doc.setDrawColor(229, 231, 235);
      doc.setLineWidth(0.25);
      doc.roundedRect(bx, s.y, boxW, 20, 2, 2, 'FD');
      setFont(17, 'bold', item.color);
      const valW = doc.getTextWidth(String(item.value));
      doc.text(String(item.value), bx + (boxW - valW) / 2, s.y + 11);
      setFont(6, 'bold', GRAY);
      const labelW = doc.getTextWidth(item.label.toUpperCase());
      doc.text(item.label.toUpperCase(), bx + (boxW - labelW) / 2, s.y + 17);
    });
    s.y += 27;

    // Lista de ciclos formativos presentes en el grupo
    const ciclos = [...new Set(retos.map(r => r.ciclo).filter(Boolean))].sort();
    if (ciclos.length > 0) {
      setFont(6.5, 'bold', GRAY);
      doc.text(`${ciclos.length} CICLO${ciclos.length !== 1 ? 'S' : ''} FORMATIVO${ciclos.length !== 1 ? 'S' : ''}`, MARGIN, s.y);
      s.y += 5;
      ciclos.forEach(ciclo => {
        setFont(8, 'normal', LGRAY);
        const cLines = doc.splitTextToSize(`• ${ciclo}`, CONTENT_W - 4);
        doc.text(cLines, MARGIN + 2, s.y);
        s.y += cLines.length * 4.2 + 0.8;
      });
      s.y += 4;
    }

    // Separador
    doc.setDrawColor(229, 231, 235);
    doc.setLineWidth(0.5);
    doc.line(MARGIN, s.y, MARGIN + CONTENT_W, s.y);
    s.y += 8;

    // Cabecera del índice
    setFont(7.5, 'bold', DARK);
    doc.text('ÍNDICE DE MICRO-RETOS', MARGIN, s.y);
    s.y += 8;

    // Listado de retos, agrupados por ciclo cuando hay más de uno
    // Se limita a MAX_INDEX entradas para no saturar la portada
    const MAX_INDEX = 45;
    const displayRetos = retos.slice(0, MAX_INDEX);

    if (ciclos.length > 1) {
      ciclos.forEach(ciclo => {
        const cicloRetos = displayRetos.filter(r => r.ciclo === ciclo);
        if (!cicloRetos.length) return;

        checkBreak(12);
        setFont(6.5, 'bold', GREEN);
        const cicloW = doc.getTextWidth(ciclo.toUpperCase());
        doc.text(ciclo.toUpperCase(), MARGIN, s.y);
        doc.setDrawColor(...GREEN);
        doc.setLineWidth(0.2);
        doc.line(MARGIN, s.y + 1.5, MARGIN + cicloW, s.y + 1.5);
        s.y += 6;

        cicloRetos.forEach(reto => {
          const num = retos.indexOf(reto) + 1;
          checkBreak(9);
          setFont(7.5, 'bold', DARK);
          doc.text(`${num}.`, MARGIN + 2, s.y);
          setFont(7.5, 'normal', DARK);
          const tLine = doc.splitTextToSize(reto.titulo || 'Sin título', CONTENT_W - 14)[0];
          doc.text(tLine, MARGIN + 9, s.y);
          s.y += 4.5;
          const meta = [
            reto.empresa_nombre,
            reto.nivel_grupo ? `Nivel ${reto.nivel_grupo}` : null,
          ].filter(Boolean).join(' · ');
          if (meta) {
            setFont(6.5, 'normal', GRAY);
            doc.text(meta, MARGIN + 9, s.y);
            s.y += 4;
          }
        });
        s.y += 3;
      });
    } else {
      displayRetos.forEach((reto, i) => {
        checkBreak(9);
        setFont(7.5, 'bold', DARK);
        doc.text(`${i + 1}.`, MARGIN + 2, s.y);
        setFont(7.5, 'normal', DARK);
        const tLine = doc.splitTextToSize(reto.titulo || 'Sin título', CONTENT_W - 14)[0];
        doc.text(tLine, MARGIN + 9, s.y);
        s.y += 4.5;
        const meta = [
          reto.empresa_nombre,
          reto.nivel_grupo ? `Nivel ${reto.nivel_grupo}` : null,
        ].filter(Boolean).join(' · ');
        if (meta) {
          setFont(6.5, 'normal', GRAY);
          doc.text(meta, MARGIN + 9, s.y);
          s.y += 4;
        }
      });
    }

    if (retos.length > MAX_INDEX) {
      checkBreak(8);
      setFont(7.5, 'italic', GRAY);
      doc.text(`... y ${retos.length - MAX_INDEX} micro-retos más`, MARGIN + 2, s.y);
    }
  };

  return { renderReto, renderCoverPage };
}

// ── API pública ───────────────────────────────────────────────────────────────

export function usePdfExport() {
  // PDF individual de un único microreto (comportamiento original)
  const descargarPDF = (reto) => {
    const doc = new jsPDF({ unit: 'mm', format: 'a4', compress: true });
    const { renderReto } = makeRenderer(doc);
    renderReto(reto);
    addFooters(doc);
    doc.save(`microreto-${slugify(reto.titulo)}.pdf`);
  };

  // PDF de grupo: portada con índice + un microreto por sección a partir de la página 2
  const descargarPDFGrupo = (retos, titulo, subtitulo = '') => {
    if (!retos?.length) return;
    const doc = new jsPDF({ unit: 'mm', format: 'a4', compress: true });
    const { renderReto, renderCoverPage } = makeRenderer(doc);

    renderCoverPage(retos, titulo, subtitulo);

    retos.forEach(reto => {
      doc.addPage();
      renderReto(reto);
    });

    // ── FOOTER en cada página ────────────────────────────────────────────
    addFooters(doc);
    doc.save(`microretos-${slugify(titulo)}.pdf`);
  };

  return { descargarPDF, descargarPDFGrupo };
}
