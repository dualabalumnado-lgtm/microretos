import { jsPDF } from 'jspdf';

const GREEN  = [0, 168, 89];
const DARK   = [31, 41, 55];
const GRAY   = [107, 114, 128];
const LGRAY  = [75, 85, 99];
const BLUE   = [37, 99, 235];

const PAGE_W    = 210;
const PAGE_H    = 297;
const MARGIN    = 14;
const CONTENT_W = PAGE_W - MARGIN * 2;

function slugify(text) {
  return (text || 'documento')
    .toLowerCase()
    .normalize('NFD').replace(/[̀-ͯ]/g, '')
    .replace(/\s+/g, '-')
    .replace(/[^a-z0-9-]/g, '');
}

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

function makeRenderer(doc) {
  const s = { y: 0 };

  const checkBreak = (needed = 20) => {
    if (s.y + needed > PAGE_H - 14) {
      doc.addPage();
      s.y = 14;
    }
  };

  const setFont = (size, style = 'normal', color = LGRAY) => {
    doc.setFontSize(size);
    doc.setFont('helvetica', style);
    doc.setTextColor(...color);
  };

  const addSectionTitle = (text, color = DARK) => {
    checkBreak(14);
    s.y += 2;
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

  const renderProyecto = (p) => {
    s.y = 0;

    // ── BANDA SUPERIOR ────────────────────────────────────────────────────
    doc.setFillColor(...GREEN);
    doc.rect(0, 0, PAGE_W, 8, 'F');
    setFont(6, 'bold', [255, 255, 255]);
    doc.text('DUALAB · FICHA DE MICROPROYECTO', MARGIN, 5.5);

    s.y = 14;

    // Título
    setFont(18, 'bold', DARK);
    const titleLines = doc.splitTextToSize(p.titulo || 'Sin título', CONTENT_W);
    doc.text(titleLines, MARGIN, s.y);
    s.y += titleLines.length * 7 + 2;

    // Badges: estado, empresa, ciclo, centro
    let bx = MARGIN;
    const estadoColors = {
      publicado: { bg: [209, 250, 229], txt: GREEN },
      borrador:  { bg: [254, 243, 199], txt: [161, 128, 0] },
      archivado: { bg: [243, 244, 246], txt: GRAY },
    };
    const ec = estadoColors[p.estado] || estadoColors.borrador;
    bx += drawBadge((p.estado || 'borrador').toUpperCase(), ec.bg, ec.txt, bx, s.y);
    if (p.empresa_nombre)  bx += drawBadge(p.empresa_nombre,  DARK,            [255,255,255], bx, s.y);
    if (p.ciclo_nombre)    bx += drawBadge(p.ciclo_nombre,    [209, 250, 229], GREEN,         bx, s.y);
    if (p.centro_nombre)   drawBadge(p.centro_nombre,          [243, 244, 246], GRAY,          bx, s.y);
    s.y += 10;

    // Separador
    doc.setDrawColor(229, 231, 235);
    doc.setLineWidth(0.3);
    doc.line(MARGIN, s.y, MARGIN + CONTENT_W, s.y);
    s.y += 6;

    // ── EMPRESA ───────────────────────────────────────────────────────────
    const emp = p.datos_empresa;
    if (emp?.nombre) {
      addSectionTitle('Empresa', GREEN);
      setFont(9, 'bold', DARK);
      doc.text(emp.nombre, MARGIN, s.y);
      s.y += 5;
      if (emp.sector) {
        setFont(8, 'normal', GRAY);
        doc.text(emp.sector, MARGIN, s.y);
        s.y += 4.5;
      }
      if (emp.persona_contacto || emp.email) {
        setFont(8, 'normal', LGRAY);
        const contacto = [emp.persona_contacto, emp.email].filter(Boolean).join(' · ');
        doc.text(contacto, MARGIN, s.y);
        s.y += 4.5;
      }
      if (emp.descripcion) addParagraph(emp.descripcion);
    }

    // ── EQUIPO ────────────────────────────────────────────────────────────
    if (p.equipo?.alumnos?.length) {
      addSectionTitle(`Equipo (${p.equipo.alumnos.length} personas)`, DARK);
      p.equipo.alumnos.forEach(a => {
        checkBreak(6);
        const nombre = a.nombre || '';
        const rol    = a.rol ? ` · ${a.rol}` : '';
        setFont(8.5, 'normal', LGRAY);
        doc.text(`• ${nombre}`, MARGIN, s.y);
        if (a.rol) {
          const nw = doc.getTextWidth(`• ${nombre}`);
          setFont(8, 'normal', GRAY);
          doc.text(rol, MARGIN + nw, s.y);
        }
        s.y += 4.8;
      });
      s.y += 2;
    }

    // ── EL RETO ───────────────────────────────────────────────────────────
    const reto = p.diseno_reto;
    if (reto?.descripcion || reto?.pregunta_reto) {
      addSectionTitle('El reto', GREEN);

      if (reto.pregunta_reto) {
        const pregLines = doc.splitTextToSize(`"${reto.pregunta_reto}"`, CONTENT_W - 10);
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
        s.y += boxH + 6;
      }

      if (reto.descripcion) addParagraph(reto.descripcion);

      if (reto.entregables) {
        setFont(7, 'bold', GRAY);
        doc.text('ENTREGABLES', MARGIN, s.y);
        s.y += 4;
        addParagraph(reto.entregables);
      }
    }

    // ── MÓDULOS ───────────────────────────────────────────────────────────
    if (p.modulos_seleccionados?.length) {
      addSectionTitle(`Módulos (${p.modulos_seleccionados.length})`, DARK);
      const nombres = p.modulos_seleccionados.map(m => m.nombre || m).filter(Boolean);
      addBulletList(nombres, GREEN);
    }

    // ── RA/CE ─────────────────────────────────────────────────────────────
    if (p.ra_ce) {
      addSectionTitle('Resultados de aprendizaje y criterios de evaluación', DARK);
      addParagraph(p.ra_ce);
    }

    // ── FASES DEL PROYECTO ────────────────────────────────────────────────
    const fases = p.diseno_microproyecto?.fases;
    if (fases?.length) {
      addSectionTitle('Fases del proyecto', BLUE);
      fases.forEach((f, i) => {
        const nombre     = f.nombre || `Fase ${i + 1}`;
        const duracion   = f.duracion ? ` (${f.duracion})` : '';
        const descLines  = f.descripcion ? doc.splitTextToSize(f.descripcion, CONTENT_W - 12) : [];
        const boxH = 8 + (descLines.length ? descLines.length * 4.2 + 4 : 0);
        checkBreak(boxH + 4);

        doc.setFillColor(249, 250, 251);
        doc.setDrawColor(229, 231, 235);
        doc.setLineWidth(0.2);
        doc.roundedRect(MARGIN, s.y, CONTENT_W, boxH, 2, 2, 'FD');

        // Número de fase
        doc.setFillColor(...GREEN, 0.15);
        doc.setFillColor(209, 250, 229);
        doc.circle(MARGIN + 5.5, s.y + boxH / 2, 3.5, 'F');
        setFont(7, 'bold', GREEN);
        const numW = doc.getTextWidth(String(i + 1));
        doc.text(String(i + 1), MARGIN + 5.5 - numW / 2, s.y + boxH / 2 + 2);

        let iy = s.y + 5;
        setFont(8.5, 'bold', DARK);
        doc.text(`${nombre}${duracion}`, MARGIN + 12, iy);
        if (descLines.length) {
          iy += 4.5;
          setFont(7.5, 'normal', LGRAY);
          doc.text(descLines, MARGIN + 12, iy);
        }
        s.y += boxH + 3;
      });
      s.y += 2;
    }

    // ── OBJETIVOS ─────────────────────────────────────────────────────────
    if (p.objetivos?.lista?.length) {
      addSectionTitle('Objetivos', GREEN);
      addBulletList(p.objetivos.lista, GREEN);
    }

    // ── KPIs ──────────────────────────────────────────────────────────────
    if (p.kpis?.lista?.length) {
      addSectionTitle('KPIs', DARK);
      p.kpis.lista.forEach(kpi => {
        const lines = doc.splitTextToSize(String(kpi), CONTENT_W - 6);
        checkBreak(lines.length * 4.5 + 2);
        setFont(8.5, 'bold', GREEN);
        doc.text('✓', MARGIN, s.y);
        setFont(8.5, 'normal', LGRAY);
        doc.text(lines, MARGIN + 5, s.y);
        s.y += lines.length * 4.5 + 1.5;
      });
      s.y += 2;
    }

    // ── VALIDACIÓN EMPRESA ────────────────────────────────────────────────
    const val = p.validacion_empresa;
    if (val?.respuestas && Object.keys(val.respuestas).length) {
      addSectionTitle('Validación empresa', GREEN);
      const entries = Object.entries(val.respuestas);
      entries.forEach(([key, v]) => {
        checkBreak(8);
        const label = key.replace(/_/g, ' ');
        setFont(7, 'bold', GRAY);
        doc.text(label.toUpperCase(), MARGIN, s.y);
        const valColor = v === 'Sí' ? GREEN : v === 'No' ? [220, 38, 38] : [161, 128, 0];
        setFont(8, 'bold', valColor);
        doc.text(String(v), MARGIN + doc.getTextWidth(label.toUpperCase()) + 4, s.y);
        s.y += 5;
      });
      if (val.comentarios) {
        s.y += 2;
        setFont(7, 'bold', GRAY);
        doc.text('COMENTARIOS', MARGIN, s.y);
        s.y += 4.5;
        addParagraph(val.comentarios);
      }
      s.y += 2;
    }

    // ── RESUMEN EJECUTIVO ─────────────────────────────────────────────────
    if (p.resumen?.texto) {
      addSectionTitle('Resumen ejecutivo', DARK);
      addParagraph(p.resumen.texto);
    }
  };

  return { renderProyecto };
}

export function useMicroproyectoPdfExport() {
  const descargarPDF = (proyecto) => {
    const doc = new jsPDF({ unit: 'mm', format: 'a4', compress: true });
    const { renderProyecto } = makeRenderer(doc);
    renderProyecto(proyecto);
    addFooters(doc);
    doc.save(`microproyecto-${slugify(proyecto.titulo)}.pdf`);
  };

  return { descargarPDF };
}
