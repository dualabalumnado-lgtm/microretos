<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
import api from '../api.js'; 

const familias = ref([]); 
const ciclos = ref([]);
const modulos = ref([]);

// --- ESTADO PARA ANIMACIONES DE ENTRADA ---
const isLoaded = ref(false);

// --- CONTROL DE NAVEGACIÓN ---
const pasoActual = ref(1); 
const totalPasos = 3;

// --- VARIABLES B2B ---
const empresas = ref([]);
const familiasFiltradas = ref([]); 
const buscadorEmpresa = ref('');
const mostrarDropdownEmpresas = ref(false);
const empresaDetalle = ref(null); 

// --- ESTADO MODO DEMO Y SIMULACIÓN ---
const esModoDemo = ref(false);
const esInfoSimulada = ref(false); // NUEVO: Estado para el botón de Información Simulada
let autoSelectDemoCycle = false; 

// --- FILTRO DE COLEGIOS ---
const centroFiltro = ref('');
const centrosDisponibles = computed(() => {
  const centros = empresas.value.map(e => e.centro_educativo).filter(Boolean);
  return [...new Set(centros)].sort();
});

const seleccion = ref({
  empresaId: '', 
  empresaNombre: '',
  empresaCentro: '', 
  empresaSector: '',
  empresaUbicacion: '',
  empresaTamano: '', 
  empresaWeb: '',
  
  diaANormal: '',        
  friccionArea: '',      
  friccionProblema: '', 
  restricciones: [],    
  otraLimitacion: '',   
  loQueNoQuieren: '',   
  consecuencias: [],    
  otraConsecuencia: '', 
  expectativasAlumno: '', 

  familia: '',
  cicloId: '', 
  cursoSeleccionado: 2,
  duracion: '1 a 2 semanas',
  nivelGrupo: 'Medio',
  
  cantidadMicroretos: 3, 
});

const modulosSeleccionados = ref([]); 

const modulosDelCurso = computed(() => {
  return modulos.value.filter(m => m.curso == seleccion.value.cursoSeleccionado);
});

const microretosGenerados = ref([]);
const cargando = ref(false);
const actualizandoCRM = ref(false);
const crmActualizado = ref(false);
const diagnosticoRecuperado = ref(false);

const guardandoTodos = ref(false);
const todosGuardados = ref(false);

const consecuenciasOpciones = [
  'Errores frecuentes', 'Costes innecesarios', 'Pérdida de tiempo', 
  'Insatisfacción del cliente', 'Riesgos de seguridad', 'Desperdicio de materiales', 'Falta de comunicación interna'
];

const limitacionesOpciones = [
  'Presupuesto Cero/Muy Bajo', 'Equipos obsoletos', 'Internet inestable',
  'Software cerrado', 'Resistencia al cambio', 'Espacio reducido',
  'Falta de tiempo', 'Normativa RGPD'
];

const popupActivo = ref(null); 
const abrirPopup = (tipo, id) => { popupActivo.value = { tipo, id }; };
const cerrarPopup = () => { popupActivo.value = null; };

const ayudas = {
  1: { 
    info: "Detalla la actividad principal de la empresa, su modelo de negocio y quiénes conforman el equipo. Esto ayuda a la IA a dimensionar la solución.", 
    ejemplo: "Somos una agencia de logística especializada en última milla para e-commerce. Contamos con 15 repartidores y 3 personas en oficina gestionando rutas y atención al cliente." 
  },
  2: { 
    info: "Identifica el 'cuello de botella' o proceso que genera más fricción, consume más tiempo absurdo o provoca más errores en el día a día.", 
    ejemplo: "La planificación matutina de rutas. Actualmente se hace en una pizarra y cruzando hojas de Excel, lo que provoca que los repartidores salgan tarde y se crucen en las mismas zonas de la ciudad." 
  },
  3: { 
    info: "Menciona intentos previos de solución o barreras reales (presupuesto, conocimientos técnicos, resistencia al cambio) que impidan resolverlo fácilmente.", 
    ejemplo: "Probamos un software de pago corporativo pero era muy complejo y los repartidores no lo usaban. Además, no tenemos presupuesto para comprar tablets nuevas para todos los vehículos." 
  },
  4: { 
    info: "Visualiza el escenario ideal. ¿Qué cambiaría radicalmente en la empresa si este problema se resolviera hoy mismo?", 
    ejemplo: "Ahorraríamos 2 horas de trabajo administrativo al día, reduciríamos el gasto en combustible un 15% y los clientes recibirían sus paquetes mucho antes." 
  },
  5: { 
    info: "Acota qué esperas que el alumnado produzca como resultado final del reto (un diseño, una maqueta, un plan, un script, un prototipo...).", 
    ejemplo: "Esperamos que analicen el flujo actual y diseñen un prototipo funcional en AppSheet o Glide (herramientas No-Code) que permita a los repartidores ver su ruta optimizada en su propio móvil." 
  }
};

const getDatosPaso2Preparados = () => {
  let limitacionesFinales = [...seleccion.value.restricciones];
  if (seleccion.value.otraLimitacion) limitacionesFinales.push(seleccion.value.otraLimitacion);
  const restriccionesStr = limitacionesFinales.join(', ');
  
  let consecuenciasFinales = [...seleccion.value.consecuencias];
  if (seleccion.value.otraConsecuencia) consecuenciasFinales.push(seleccion.value.otraConsecuencia);
  const consecuenciasStr = consecuenciasFinales.join(', ');
  
  return { restriccionesStr, consecuenciasStr, consecuenciasArray: consecuenciasFinales };
};

const empresasFiltradasBusqueda = computed(() => {
  let filtradas = empresas.value;
  if (centroFiltro.value) {
    filtradas = filtradas.filter(e => e.centro_educativo === centroFiltro.value);
  }
  if (buscadorEmpresa.value) { 
    filtradas = filtradas.filter(e => 
      e.nombre_comercial?.toLowerCase().includes(buscadorEmpresa.value.toLowerCase())
    );
  }
  return filtradas;
});

const seleccionarEmpresa = (emp) => {
  seleccion.value.empresaId = emp.id;
  seleccion.value.empresaNombre = emp.nombre_comercial;
  buscadorEmpresa.value = emp.nombre_comercial;
  mostrarDropdownEmpresas.value = false;
};

const onBuscadorInput = () => {
  mostrarDropdownEmpresas.value = true;
  // Si el usuario borra el campo, limpiamos la empresa seleccionada
  if (!buscadorEmpresa.value) {
    seleccion.value.empresaId = '';
    seleccion.value.empresaNombre = '';
    empresaDetalle.value = null;
  }
};

watch(buscadorEmpresa, (val) => {
  if (val && empresas.value.length > 0) {
    mostrarDropdownEmpresas.value = true;
  }
});

// --- FUNCIÓN LIMPIAR FORMULARIO (BOTÓN ROJO) ---
const limpiarFormulario = () => {
  if(confirm("¿Estás seguro de que quieres vaciar el formulario y empezar de cero?")) {
    pasoActual.value = 1;
    buscadorEmpresa.value = '';
    centroFiltro.value = ''; // Limpiamos el filtro del colegio también
    empresaDetalle.value = null;
    microretosGenerados.value = [];
    crmActualizado.value = false;
    diagnosticoRecuperado.value = false;
    todosGuardados.value = false;
    esModoDemo.value = false;
    esInfoSimulada.value = false; // Añadido: desmarca el botón al vaciar
    modulosSeleccionados.value = [];
    
    seleccion.value = {
      empresaId: '', empresaNombre: '', empresaCentro: '', empresaSector: '',
      empresaUbicacion: '', empresaTamano: '', empresaWeb: '',
      diaANormal: '', friccionArea: '', friccionProblema: '', restricciones: [],
      otraLimitacion: '', loQueNoQuieren: '', consecuencias: [], otraConsecuencia: '', expectativasAlumno: '',
      familia: '', cicloId: '', cursoSeleccionado: 2, duracion: '1 a 2 semanas', nivelGrupo: 'Medio', cantidadMicroretos: 3
    };
    window.scrollTo({top: 0, behavior: 'smooth'});
  }
};

// --- FUNCIÓN PARA CARGAR DATOS DEMO EXACTOS ---
const cargarDemo = () => {
  esModoDemo.value = true;
  
  seleccion.value.empresaId = '';
  centroFiltro.value = "IES Ana Luisa de Benítez"; 
  buscadorEmpresa.value = "VirtualNest SL"; 
  empresaDetalle.value = null;
  diagnosticoRecuperado.value = false;
  
  // Datos Paso 1
  seleccion.value.empresaNombre = "VirtualNest SL";
  seleccion.value.empresaSector = "Informática y Comunicaciones";
  seleccion.value.empresaTamano = "Mediana (50-250)";
  seleccion.value.empresaWeb = "www.virtualnest.demo";
  seleccion.value.empresaCentro = "IES Ana Luisa de Benítez";
  
  // Datos Paso 2 
  seleccion.value.diaANormal = "Desarrollamos software y simuladores de realidad virtual para formación corporativa e industrial. Tenemos un equipo de 20 programadores, 5 diseñadores 3D y personal de Calidad (QA).";
  seleccion.value.friccionArea = "Testing y reporte de bugs en entornos 3D interactivos.";
  seleccion.value.friccionProblema = "Los testers tienen que quitarse las gafas de realidad virtual (VR) cada vez que encuentran un fallo para anotarlo en un Excel o Jira en el ordenador. Esto corta la inmersión, pierden el hilo y se olvidan de detalles visuales exactos del bug.";
  seleccion.value.restricciones = ["Software cerrado", "Falta de tiempo"];
  seleccion.value.otraLimitacion = "No podemos integrar plugins externos pesados en el motor gráfico actual sin romper el rendimiento y los FPS del simulador.";
  seleccion.value.loQueNoQuieren = "Un sistema de tickets tradicional fuera del entorno de las gafas VR.";
  seleccion.value.consecuencias = ["Pérdida de tiempo", "Errores frecuentes"];
  seleccion.value.otraConsecuencia = "Bugs difíciles de reproducir por falta de contexto visual y coordenadas exactas.";
  seleccion.value.expectativasAlumno = "Que investigue soluciones de captura de datos in-app o proponga un flujo mediante scripts ligeros que permita registrar coordenadas (X, Y, Z) y capturas de pantalla pulsando un solo botón en los mandos VR, guardándolo automáticamente.";
  
  // Datos Paso 3 
  familiasFiltradas.value = ["Informática y Comunicaciones"];
  seleccion.value.nivelGrupo = "Alto";
  seleccion.value.cursoSeleccionado = 2;
  
  autoSelectDemoCycle = true; 
  seleccion.value.familia = "Informática y Comunicaciones";
  
  alert("¡Modo Demo activado! Se ha cargado el caso de VirtualNest SL.");
};

const paso1Valido = computed(() => seleccion.value.empresaNombre && seleccion.value.empresaSector && seleccion.value.empresaTamano);
const paso2Valido = computed(() => seleccion.value.diaANormal && seleccion.value.friccionProblema);
const paso3Valido = computed(() => seleccion.value.familia && seleccion.value.cicloId && seleccion.value.cursoSeleccionado); 

// Cierra el dropdown al hacer clic fuera
const buscadorRef = ref(null);
const cerrarDropdownFuera = (e) => {
  if (buscadorRef.value && !buscadorRef.value.contains(e.target)) {
    mostrarDropdownEmpresas.value = false;
  }
};

onMounted(async () => {
  document.addEventListener('click', cerrarDropdownFuera);
  setTimeout(() => { isLoaded.value = true; }, 100);
  try {
    const res = await api.get('/empresas');
    empresas.value = res.data;
  } catch (e) { console.error(e); }
});

onUnmounted(() => {
  document.removeEventListener('click', cerrarDropdownFuera);
});

watch(() => seleccion.value.empresaId, async (nuevoId) => {
  seleccion.value.familia = ''; ciclos.value = []; seleccion.value.cicloId = ''; modulos.value = [];
  modulosSeleccionados.value = []; familiasFiltradas.value = [];
  empresaDetalle.value = null; crmActualizado.value = false; diagnosticoRecuperado.value = false;
  microretosGenerados.value = []; 
  
  if (!nuevoId) return;

  const emp = empresas.value.find(e => String(e.id) === String(nuevoId));
  if(emp) {
    empresaDetalle.value = emp; 
    seleccion.value.empresaNombre = emp.nombre_comercial;
    seleccion.value.empresaCentro = emp.centro_educativo || ''; 
    seleccion.value.empresaSector = emp.sector || ''; 
    seleccion.value.empresaTamano = emp.tamano || ''; 
    seleccion.value.empresaWeb = emp.web || '';
    seleccion.value.empresaUbicacion = [emp.municipio, emp.provincia].filter(Boolean).join(', ');

    if (emp.friccion_area && emp.friccion_problema) {
      diagnosticoRecuperado.value = true;
      seleccion.value.diaANormal = emp.dia_a_normal || '';
      seleccion.value.friccionArea = emp.friccion_area;
      seleccion.value.friccionProblema = emp.friccion_problema;
      seleccion.value.loQueNoQuieren = emp.lo_que_no_quieren || '';
      if (emp.consecuencias) seleccion.value.consecuencias = emp.consecuencias.split(',').map(s => s.trim());
      if (emp.restricciones) seleccion.value.restricciones = emp.restricciones.split(',').map(s => s.trim());
    }
  }
  try {
    const resFam = await api.get(`/empresas/${nuevoId}/familias`);
    familiasFiltradas.value = resFam.data;
  } catch(e) { console.error(e); }
});

watch(() => seleccion.value.familia, async (val) => {
  if (!val) return;
  const url = seleccion.value.empresaCentro 
    ? `/familias/${encodeURIComponent(val)}/ciclos?centro=${encodeURIComponent(seleccion.value.empresaCentro)}`
    : `/familias/${encodeURIComponent(val)}/ciclos`;

  const res = await api.get(url);
  ciclos.value = res.data;
  
  if (autoSelectDemoCycle && ciclos.value.length > 0) {
     const cicloWeb = ciclos.value.find(c => c.nombre.includes('Web') || c.nombre.includes('Aplicaciones') || c.nombre.includes('Sistemas'));
     seleccion.value.cicloId = cicloWeb ? cicloWeb.id : ciclos.value[0].id;
     autoSelectDemoCycle = false;
  } else {
     seleccion.value.cicloId = ''; 
  }
  
  modulos.value = []; modulosSeleccionados.value = [];
});

watch(() => seleccion.value.cicloId, async (val) => {
  if (!val) return;
  const res = await api.get(`/ciclos/${val}/modulos`);
  modulos.value = res.data;
  modulosSeleccionados.value = [];
});

watch(() => seleccion.value.cursoSeleccionado, () => {
  modulosSeleccionados.value = [];
});

const avanzarPaso = () => { if (pasoActual.value < totalPasos) pasoActual.value++; window.scrollTo({top: 0, behavior: 'smooth'}); };
const retrocederPaso = () => { if (pasoActual.value > 1) pasoActual.value--; window.scrollTo({top: 0, behavior: 'smooth'}); };

const guardarInfoEmpresa = async () => {
  actualizandoCRM.value = true;
  const datosP2 = getDatosPaso2Preparados();
  
  const payload = {
    nombreComercial: seleccion.value.empresaNombre, centroEducativo: seleccion.value.empresaCentro, sector: seleccion.value.empresaSector, tamano: seleccion.value.empresaTamano, web: seleccion.value.empresaWeb,
    diaANormal: seleccion.value.diaANormal, friccionArea: seleccion.value.friccionArea, friccionProblema: seleccion.value.friccionProblema, consecuencias: datosP2.consecuenciasStr, restricciones: datosP2.restriccionesStr, loQueNoQuieren: seleccion.value.loQueNoQuieren,
    familia: seleccion.value.familia
  };

  try {
    if (seleccion.value.empresaId) {
      await api.put(`/empresas/${seleccion.value.empresaId}`, payload);
      const emp = empresas.value.find(e => String(e.id) === String(seleccion.value.empresaId));
      if(emp) Object.assign(emp, { ...payload, centro_educativo: payload.centroEducativo, dia_a_normal: payload.diaANormal, friccion_area: payload.friccionArea, friccion_problema: payload.friccionProblema, lo_que_no_quieren: payload.loQueNoQuieren });
    } else {
      const res = await api.post('/empresas', payload);
      seleccion.value.empresaId = res.data.empresa.id;
      empresas.value.push(res.data.empresa);
    }
    crmActualizado.value = true;
    setTimeout(() => crmActualizado.value = false, 3000); 
  } catch(e) { alert("Error al procesar la empresa en la BD."); } finally { actualizandoCRM.value = false; }
};

const generarReto = async () => {
  cargando.value = true;
  todosGuardados.value = false; 
  try {
    const nombresModulosSeleccionados = modulosSeleccionados.value.map(id => modulos.value.find(m => m.id === id)?.nombre).filter(Boolean); 
    const moduloNombreTxt = nombresModulosSeleccionados.length > 0 ? nombresModulosSeleccionados.join(' y ') : `A determinar por IA (${seleccion.value.cursoSeleccionado}º Curso)`;
    const datosP2 = getDatosPaso2Preparados();
    
    const res = await api.post('/generar-microreto', {
      ...seleccion.value, restricciones: datosP2.restriccionesStr, consecuencias: datosP2.consecuenciasArray,
      ciclo_nombre: ciclos.value.find(c => c.id === seleccion.value.cicloId)?.nombre, modulo_nombre: moduloNombreTxt,
      ciclo_id: seleccion.value.cicloId, modulo_id: modulosSeleccionados.value.length > 0 ? modulosSeleccionados.value : null,
      nivelGrupo: seleccion.value.nivelGrupo, expectativasAlumno: seleccion.value.expectativasAlumno, 
      cursoSeleccionado: seleccion.value.cursoSeleccionado,
      cantidad: seleccion.value.cantidadMicroretos 
    });

    if (res.data && res.data.microretos) {
        res.data.microretos.forEach(reto => {
            microretosGenerados.value.unshift({ 
                ...reto,
                _ui_guardado: false,
                _ui_guardando: false
            });
        });
    }

    setTimeout(() => { window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' }); }, 300);
  } catch (e) { console.error(e); alert("Error al contactar con la IA"); } finally { cargando.value = false; }
};

const guardarTodos = async () => {
  guardandoTodos.value = true;
  try {
    const nombresModulosSeleccionados = modulosSeleccionados.value
      .map(id => modulos.value.find(m => m.id === id)?.nombre)
      .filter(Boolean);
    const moduloStr = nombresModulosSeleccionados.length > 0 
      ? nombresModulosSeleccionados.join(' y ') 
      : 'Transversal';
    const cicloStr = ciclos.value.find(c => c.id === seleccion.value.cicloId)?.nombre;

    const payload = microretosGenerados.value
      .filter(r => !r._ui_guardado)
      .map(({ _ui_guardado, _ui_guardando, ...retoLimpio }) => ({  //Desestructuración para eliminar claves UI
        ...retoLimpio,
        ciclo: cicloStr,
        modulo: moduloStr,
        duracion: seleccion.value.duracion,
        nivel_grupo: seleccion.value.nivelGrupo
      }));

    if (payload.length > 0) {
      await api.post('/guardar-microretos-lote', { microretos: payload });
      microretosGenerados.value.forEach(r => r._ui_guardado = true);
      todosGuardados.value = true;
    } else {
      alert("Todos los retos mostrados ya estaban guardados.");
    }
  } catch (e) { 
    console.error("Error al guardar el lote:", e);
    alert("Error al guardar el lote de microretos en BD"); 
  } finally { 
    guardandoTodos.value = false; 
  }
};

const guardar = async (index) => {
  const reto = microretosGenerados.value[index];
  reto._ui_guardando = true;
  try {
    const nombresModulosSeleccionados = modulosSeleccionados.value
      .map(id => modulos.value.find(m => m.id === id)?.nombre)
      .filter(Boolean);

    const { _ui_guardado, _ui_guardando, ...retoLimpio } = reto; //Desestructuración para eliminar claves UI

    await api.post('/guardar-microreto-bd', { 
      ...retoLimpio,
      ciclo: ciclos.value.find(c => c.id === seleccion.value.cicloId)?.nombre,
      modulo: nombresModulosSeleccionados.length > 0 
        ? nombresModulosSeleccionados.join(' y ') 
        : 'Transversal',
      duracion: seleccion.value.duracion,
      nivel_grupo: seleccion.value.nivelGrupo
    });
    reto._ui_guardado = true;
  } catch (e) { 
    console.error("Error al guardar:", e);
    alert("Error al guardar este microreto"); 
  } finally { 
    reto._ui_guardando = false; 
  }
};

// Computed que indica si hay "contexto de empresa" suficiente
const tieneContextoEmpresa = computed(() => {
  return !!(seleccion.value.empresaId || seleccion.value.empresaNombre || buscadorEmpresa.value);
});

</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] p-4 md:p-12 transition-colors duration-500 font-sans text-[#1F2937] overflow-x-hidden">
    <div class="max-w-6xl mx-auto">
      
      <header class="mb-10 text-center flex flex-col items-center">
        <div class="inline-flex items-center mb-8 bg-[#1F2937] py-4 pr-10 pl-6 rounded-[3rem] shadow-lg border border-[#333333] transition-all duration-1000 ease-out transform"
             :class="isLoaded ? 'translate-y-0 opacity-100' : '-translate-y-10 opacity-0'">
          <img src="../assets/logo.png" alt="Logo DuaLab" class="h-32 md:h-40 w-auto object-contain -mr-4 md:-mr-8 relative z-10" />
          <span class="font-black text-4xl md:text-5xl tracking-tighter uppercase text-white italic relative z-20">
            Dua<span class="text-[#00A859]">Lab</span><span class="text-[#99CC33] not-italic text-lg md:text-xl ml-1">Studio Tool</span>
          </span>
        </div>
        
        <h1 class="text-4xl md:text-5xl font-black tracking-tight mb-4 text-[#121212] transition-all duration-1000 delay-150 ease-out transform"
            :class="isLoaded ? 'translate-y-0 opacity-100' : 'translate-y-10 opacity-0'">
          Factoría de <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#00A859] to-[#99CC33]">Micro-Retos</span>
        </h1>
        
        <p class="text-gray-500 max-w-2xl mx-auto text-base md:text-lg leading-relaxed font-medium transition-all duration-1000 delay-300 ease-out transform"
           :class="isLoaded ? 'translate-y-0 opacity-100' : 'translate-y-10 opacity-0'">
          Convierte problemas empresariales reales en retos educativos clasificados por el currículo oficial.
        </p>
      </header>

      <div class="max-w-3xl mx-auto mb-16 relative transition-all duration-1000 delay-500 ease-out transform"
           :class="isLoaded ? 'translate-y-0 opacity-100' : 'translate-y-10 opacity-0'">
        <div class="flex justify-between items-center relative z-10">
          <div v-for="step in totalPasos" :key="step" class="flex flex-col items-center">
            <div class="w-12 h-12 rounded-full flex items-center justify-center font-black transition-all duration-500 shadow-sm"
              :class="pasoActual >= step ? 'bg-gradient-to-r from-[#00A859] to-[#99CC33] text-white scale-110 shadow-lg' : 'bg-white border-2 border-gray-200 text-gray-400'">
              <span v-if="pasoActual > step"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></span>
              <span v-else>{{ step }}</span>
            </div>
            <span class="text-[10px] font-black uppercase mt-3 tracking-widest text-center" :class="pasoActual >= step ? 'text-[#00A859]' : 'text-gray-400'">
              {{ step === 1 ? '1. Datos Empresa' : step === 2 ? '2. El Problema' : '3. Match Académico' }}
            </span>
          </div>
        </div>
        <div class="absolute top-6 left-0 w-full h-1 bg-gray-200 -z-0 rounded-full"></div>
        <div class="absolute top-6 left-0 h-1 bg-gradient-to-r from-[#00A859] to-[#99CC33] transition-all duration-700 -z-0 rounded-full" :style="{ width: ((pasoActual - 1) / (totalPasos - 1)) * 100 + '%' }"></div>
      </div>

      <main class="min-h-[400px]">
        <transition name="fade" mode="out-in">
          <div v-if="pasoActual === 1" class="space-y-8 animate-in slide-in-from-bottom-4 duration-500">
            <section class="bg-white rounded-[2.5rem] p-8 md:p-10 border border-gray-100 shadow-[0_20px_50px_rgb(0,0,0,0.05)]">
              
              <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-6 mb-8">
                <div class="flex items-center gap-4">
                  <div class="w-12 h-12 rounded-2xl bg-[#00A859]/10 flex items-center justify-center text-[#00A859]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                  </div>
                  <h2 class="text-2xl font-black uppercase tracking-tight text-[#1F2937]">Buscar en DuaLab</h2>
                </div>
                
                <div class="flex flex-wrap items-center gap-2">
                  <button @click="esInfoSimulada = !esInfoSimulada"
                    :class="esInfoSimulada ? 'bg-[#1F2937] text-white border-[#1F2937] shadow-md' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-200 shadow-sm'"
                    class="px-5 py-2.5 rounded-full font-bold text-xs tracking-widest uppercase transition-all flex items-center gap-2 border">
                    <svg v-if="esInfoSimulada" class="w-4 h-4 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Información Simulada
                  </button>
                  <button @click="cargarDemo" :disabled="esModoDemo" 
                    :class="esModoDemo ? 'bg-[#00A859]/10 text-[#00A859] border-[#00A859]/20 cursor-default' : 'bg-white text-[#00A859] hover:bg-gray-50 border-gray-200 hover:border-[#00A859] shadow-sm'"
                    class="px-5 py-2.5 rounded-full font-bold text-xs tracking-widest uppercase transition-all flex items-center gap-2 border">
                    <span v-if="esModoDemo">✓ DEMO ACTIVA</span>
                    <span v-else> Cargar Demo</span>
                  </button>
                  <button @click="limpiarFormulario" class="px-5 py-2.5 bg-white text-red-500 hover:bg-red-50 hover:border-red-500 border border-gray-200 rounded-full font-bold text-xs tracking-widest uppercase transition-all flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Vaciar
                  </button>
                </div>
              </div>
              
              <div class="mb-10 relative z-20 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="label-style">Filtrar por Colegio/Centro (Opcional)</label>
                  <select v-model="centroFiltro" class="input-style">
                    <option value="">Todos los centros...</option>
                    <option v-for="centro in centrosDisponibles" :key="centro" :value="centro">{{ centro }}</option>
                  </select>
                </div>
                
                <div class="relative" ref="buscadorRef">
                  <label class="label-style">Escribe para buscar empresa</label>
                  <div class="absolute left-5 top-[38px] flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                  </div>
                  
                  <input
                    v-model="buscadorEmpresa"
                    @input="onBuscadorInput"
                    @focus="mostrarDropdownEmpresas = true"
                    autocomplete="new-password"
                    name="buscar-empresa-dualab"
                    type="search"
                    class="input-style pl-14 text-lg"
                    placeholder="Ej: Fundación Sergio Alonso..."
                  />
                  
                  <Transition name="dropdown">
                    <div
                      v-if="mostrarDropdownEmpresas && empresasFiltradasBusqueda.length > 0"
                      class="absolute w-full mt-2 bg-[#1F2937] border border-[#374151] rounded-2xl shadow-2xl max-h-64 overflow-y-auto z-50"
                    >
                      <div v-if="buscadorEmpresa" class="px-6 pt-3 pb-1">
                        <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">
                          {{ empresasFiltradasBusqueda.length }} resultado(s) para "{{ buscadorEmpresa }}"
                        </span>
                      </div>
                      
                      <button
                        v-for="emp in empresasFiltradasBusqueda"
                        :key="emp.id"
                        @mousedown.prevent="seleccionarEmpresa(emp)"
                        class="w-full text-left px-6 py-4 border-b border-[#374151] hover:bg-[#374151] transition-colors last:border-0"
                      >
                        <span class="font-bold text-white block">{{ emp.nombre_comercial }}</span>
                        <span class="text-xs text-gray-400 uppercase tracking-widest">
                          {{ emp.sector || 'Sin sector especificado' }}
                        </span>
                      </button>
                    </div>
                  </Transition>

                  <!-- Mensaje cuando no hay resultados -->
                  <div
                    v-if="mostrarDropdownEmpresas && buscadorEmpresa && empresasFiltradasBusqueda.length === 0"
                    class="absolute w-full mt-2 bg-[#1F2937] border border-[#374151] rounded-2xl shadow-2xl z-50 px-6 py-5"
                  >
                    <p class="text-gray-400 text-sm">No se encontró ninguna empresa con ese nombre.</p>
                  </div>
                </div>
              </div>

            <div v-if="empresaDetalle" class="bg-gray-50 rounded-3xl p-8 border border-gray-100 mb-8 animate-in fade-in duration-500">
  
            <div class="mb-8">
              <div class="flex items-center gap-3 mb-4">
                <div class="w-2 h-6 bg-[#00A859] rounded-full"></div>
                <h3 class="font-black text-[#1F2937] uppercase tracking-widest text-sm">Ficha de Contacto</h3>
              </div>
              
              <div v-if="empresaDetalle.razon_social" class="flex items-start gap-3 ml-1">
                <svg class="w-5 h-5 mt-0.5 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1v1H9V7zm5 0h1v1h-1V7zm-5 4h1v1H9v-1zm5 0h1v1h-1v-1zm-5 4h1v1H9v-1zm5 0h1v1h-1v-1z"></path></svg>
                <div>
                  <p class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-1">Razón Social</p>
                  <p class="font-bold text-[#1F2937] text-lg leading-tight">{{ empresaDetalle.razon_social }}</p>
                </div>
              </div>
            </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pt-6 border-t border-gray-200/60">
                <div v-if="empresaDetalle.persona_contacto || empresaDetalle.telefono || empresaDetalle.email_general">
                  <p class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-1">Contacto Directo</p>
                  <p v-if="empresaDetalle.persona_contacto" class="font-semibold text-[#1F2937] text-sm">{{ empresaDetalle.persona_contacto }}</p>
                  <p v-if="empresaDetalle.telefono" class="text-gray-600 text-sm flex items-center gap-2 mt-1">
                    <svg class="w-4 h-4 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg> {{ empresaDetalle.telefono }}
                  </p>
                  <p v-if="empresaDetalle.email_general" class="text-gray-600 text-sm flex items-center gap-2 mt-1 truncate">
                    <svg class="w-4 h-4 shrink-0 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg> {{ empresaDetalle.email_general }}
                  </p>
                </div>

                <div v-if="empresaDetalle.direccion || empresaDetalle.municipio || empresaDetalle.codigo_postal">
                  <p class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-1">Ubicación</p>
                  <p class="text-sm text-gray-600 leading-tight">
                    {{ empresaDetalle.direccion }} {{ empresaDetalle.numero }} <br>
                    {{ empresaDetalle.codigo_postal }} - {{ empresaDetalle.municipio }} <span v-if="empresaDetalle.provincia">({{ empresaDetalle.provincia }})</span>
                  </p>
                </div>

                <div v-if="empresaDetalle.actividad || empresaDetalle.web">
                  <p class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-1">Actividad / Web</p>
                  <p v-if="empresaDetalle.actividad" class="text-sm text-gray-600 line-clamp-2" :title="empresaDetalle.actividad">{{ empresaDetalle.actividad }}</p>
                  <a v-if="empresaDetalle.web" :href="empresaDetalle.web" target="_blank" class="text-[#00A859] hover:underline font-bold text-sm truncate flex items-center gap-1 mt-1">
                    {{ empresaDetalle.web.replace(/^https?:\/\//, '') }}
                  </a>
                </div>
              </div>
            </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-8 relative">

                <div v-if="!tieneContextoEmpresa" class="absolute inset-0 bg-white/70 z-10 rounded-2xl flex items-center justify-center backdrop-blur-sm">
                  <span class="bg-[#1F2937] text-white px-6 py-3 rounded-full text-xs font-bold uppercase tracking-widest shadow-lg">
                    Busca una empresa o usa la Demo 👆
                  </span>
                </div>
                
                <div>
                  <label class="label-style" :class="!seleccion.empresaSector && (seleccion.empresaId || seleccion.empresaNombre) ? 'text-red-500' : ''">Sector de Actividad *</label>
                  <input v-model="seleccion.empresaSector" class="input-style" :class="!seleccion.empresaSector && (seleccion.empresaId || seleccion.empresaNombre) ? 'border-red-500 bg-red-900/30 placeholder:text-red-400 focus:border-red-500 focus:bg-red-900/40' : ''" placeholder="¡FALTA INFO! Rellénalo por favor..." />
                </div>

                <div>
                  <label class="label-style" :class="!seleccion.empresaTamano && (seleccion.empresaId || seleccion.empresaNombre) ? 'text-red-500' : ''">Tamaño de la Empresa *</label>
                  <select v-model="seleccion.empresaTamano" class="input-style" :class="!seleccion.empresaTamano && (seleccion.empresaId || seleccion.empresaNombre) ? 'border-red-500 bg-red-900/30 text-red-400 focus:border-red-500 focus:bg-red-900/40' : ''">
                    <option value="" disabled selected>¡FALTA INFO! Selecciona...</option>
                    <option value="Micropyme (1-10)">Micropyme (1 a 10 empleados)</option>
                    <option value="Pequeña (10-50)">Pequeña (10 a 50 empleados)</option>
                    <option value="Mediana (50-250)">Mediana (50 a 250 empleados)</option>
                    <option value="Grande (+250)">Grande (Más de 250 empleados)</option>
                  </select>
                </div>

                <div class="col-span-2">
                  <label class="label-style">Web (Opcional)</label>
                  <input v-model="seleccion.empresaWeb" class="input-style" placeholder="https://..." />
                </div>
              </div>
            </section>
          </div>
        </transition>

        <transition name="fade" mode="out-in">
          <div v-if="pasoActual === 2" class="space-y-8 animate-in slide-in-from-bottom-4 duration-500">
             <section class="bg-white rounded-[2.5rem] p-8 md:p-10 border border-gray-100 shadow-[0_20px_50px_rgb(0,0,0,0.05)] relative overflow-hidden">
              <div class="absolute top-0 right-0 bg-gray-50 text-[#1F2937] px-6 py-2 text-[10px] font-black uppercase tracking-widest rounded-bl-3xl border-b border-l border-gray-100">
                Entrevista de Diagnóstico
              </div>
              
              <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-6 mb-8 mt-4 xl:mt-0">
                <div class="flex items-center gap-4">
                  <div class="w-12 h-12 rounded-2xl bg-[#99CC33]/15 flex items-center justify-center text-[#00A859]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                  </div>
                  <h2 class="text-2xl font-black uppercase tracking-tight text-[#1F2937]">Realidad de la Empresa</h2>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                  <button @click="esInfoSimulada = !esInfoSimulada"
                    :class="esInfoSimulada ? 'bg-[#1F2937] text-white border-[#1F2937] shadow-md' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-200 shadow-sm'"
                    class="px-5 py-2.5 rounded-full font-bold text-xs tracking-widest uppercase transition-all flex items-center gap-2 border">
                    <svg v-if="esInfoSimulada" class="w-4 h-4 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Información Simulada
                  </button>
                  <button @click="cargarDemo" :disabled="esModoDemo" 
                    :class="esModoDemo ? 'bg-[#00A859]/10 text-[#00A859] border-[#00A859]/20 cursor-default' : 'bg-white text-[#00A859] hover:bg-gray-50 border-gray-200 hover:border-[#00A859] shadow-sm'"
                    class="px-5 py-2.5 rounded-full font-bold text-xs tracking-widest uppercase transition-all flex items-center gap-2 border">
                    <span v-if="esModoDemo">✓ DEMO ACTIVA</span>
                    <span v-else> Cargar Demo</span>
                  </button>
                  <button @click="limpiarFormulario" class="px-5 py-2.5 bg-white text-red-500 hover:bg-red-50 hover:border-red-500 border border-gray-200 rounded-full font-bold text-xs tracking-widest uppercase transition-all flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Vaciar
                  </button>
                </div>
              </div>

              <div v-if="diagnosticoRecuperado" class="mb-10 p-5 md:p-6 bg-[#00A859]/5 border border-[#00A859]/20 rounded-3xl flex gap-4 md:gap-5 items-start">
                <div class="bg-gradient-to-r from-[#00A859] to-[#99CC33] text-white p-2.5 rounded-2xl shrink-0 mt-1 shadow-md">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                  <h4 class="font-black text-[#00A859] uppercase tracking-widest text-xs mb-1">Información Previa Detectada</h4>
                  <p class="text-sm text-gray-600 leading-relaxed font-medium">
                    Hemos recuperado las respuestas de una sesión anterior. Puedes mantenerlas para generar nuevas variantes del reto o editarlas si la situación ha cambiado.
                  </p>
                </div>
              </div>

              <div class="space-y-10">
                <div>
                  <div class="flex items-center gap-2 mb-3">
                    <label class="label-style !mb-0 flex items-center gap-2">
                      <span class="bg-[#1F2937] text-white w-5 h-5 flex items-center justify-center rounded-full text-[10px]">1</span>
                      ¿Qué ofrece su empresa y qué hace en su día a día?
                    </label>
                    <button @click="abrirPopup('info', 1)" class="text-gray-400 hover:text-[#00A859] transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></button>
                    <button @click="abrirPopup('ejemplo', 1)" class="text-gray-400 hover:text-[#99CC33] transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg></button>
                  </div>
                  <textarea v-model="seleccion.diaANormal" class="input-style h-24" placeholder="Ej: Somos una empresa de servicios informáticos..."></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                  <div>
                    <div class="flex items-center gap-2 mb-3">
                      <label class="label-style !mb-0 flex items-center gap-2">
                        <span class="bg-[#1F2937] text-white w-5 h-5 flex items-center justify-center rounded-full text-[10px]">2</span>
                        ¿Qué tarea da más trabajo del que debería?
                      </label>
                      <button @click="abrirPopup('info', 2)" class="text-gray-400 hover:text-[#00A859] transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></button>
                      <button @click="abrirPopup('ejemplo', 2)" class="text-gray-400 hover:text-[#99CC33] transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg></button>
                    </div>
                    <input v-model="seleccion.friccionArea" class="input-style" placeholder="Ej: Registro manual de albaranes..." />
                  </div>
                  <div>
                    <label class="label-style flex items-center gap-2 mb-3">
                      <span class="bg-[#1F2937] text-white w-5 h-5 flex items-center justify-center rounded-full text-[10px]">2b</span>
                      ¿Por qué? Cuéntanos qué ocurre hoy
                    </label>
                    <textarea v-model="seleccion.friccionProblema" class="input-style h-24" placeholder="Se pierde mucho tiempo porque... hay errores cuando..."></textarea>
                  </div>
                </div>

                <div>
                  <div class="flex items-center gap-2 mb-4">
                    <label class="label-style !mb-0 flex items-center gap-2">
                      <span class="bg-[#1F2937] text-white w-5 h-5 flex items-center justify-center rounded-full text-[10px]">3</span>
                      ¿Han probado solucionarlo? ¿Qué limitaciones tienen?
                    </label>
                    <button @click="abrirPopup('info', 3)" class="text-gray-400 hover:text-[#00A859] transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></button>
                    <button @click="abrirPopup('ejemplo', 3)" class="text-gray-400 hover:text-[#99CC33] transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg></button>
                  </div>
                  
                  <div class="flex flex-wrap gap-2 mb-4">
                    <button v-for="opt in limitacionesOpciones" :key="opt" 
                      @click="seleccion.restricciones.includes(opt) ? seleccion.restricciones = seleccion.restricciones.filter(c => c !== opt) : seleccion.restricciones.push(opt)"
                      :class="seleccion.restricciones.includes(opt) ? 'bg-gradient-to-r from-[#00A859] to-[#99CC33] text-white border-transparent shadow-md' : 'bg-[#1F2937] text-gray-300 border-transparent hover:border-[#00A859]/50'"
                      class="px-5 py-2.5 rounded-2xl border-2 text-[10px] font-black uppercase transition-all shadow-sm">
                      {{ opt }}
                    </button>
                  </div>
                  <textarea v-model="seleccion.otraLimitacion" class="input-style h-20" placeholder="Describe aquí otros intentos de solución o detalles de las limitaciones..."></textarea>
                </div>

                <div>
                  <label class="label-style flex items-center gap-2 mb-3">
                    <span class="bg-[#1F2937] text-white w-5 h-5 flex items-center justify-center rounded-full text-[10px]">3b</span>
                    ¿Qué NO quieren bajo ningún concepto?
                  </label>
                  <input v-model="seleccion.loQueNoQuieren" class="input-style" placeholder="Ej: Nada que requiera suscripción mensual..." />
                </div>

                <div>
                  <div class="flex items-center gap-2 mb-4">
                    <label class="label-style !mb-0 flex items-center gap-2">
                      <span class="bg-[#1F2937] text-white w-5 h-5 flex items-center justify-center rounded-full text-[10px]">4</span>
                      Si pudieran mejorar algo YA mismo...
                    </label>
                    <button @click="abrirPopup('info', 4)" class="text-gray-400 hover:text-[#00A859] transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></button>
                    <button @click="abrirPopup('ejemplo', 4)" class="text-gray-400 hover:text-[#99CC33] transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg></button>
                  </div>
                  
                  <div class="flex flex-wrap gap-2 mb-4">
                    <button v-for="opt in consecuenciasOpciones" :key="opt" 
                      @click="seleccion.consecuencias.includes(opt) ? seleccion.consecuencias = seleccion.consecuencias.filter(c => c !== opt) : seleccion.consecuencias.push(opt)"
                      :class="seleccion.consecuencias.includes(opt) ? 'bg-gradient-to-r from-[#00A859] to-[#99CC33] text-white border-transparent shadow-md' : 'bg-[#1F2937] text-gray-300 border-transparent hover:border-[#99CC33]/50'"
                      class="px-5 py-2.5 rounded-2xl border-2 text-[10px] font-black uppercase transition-all shadow-sm">
                      {{ opt }}
                    </button>
                  </div>
                  <input v-model="seleccion.otraConsecuencia" class="input-style" placeholder="Otra mejora específica (Opcional)..." />
                </div>

                <div class="bg-gray-50 p-6 rounded-3xl border border-gray-100">
                  <div class="flex items-center gap-2 mb-3">
                    <label class="label-style !mb-0 flex items-center gap-2">
                      <span class="bg-[#1F2937] text-white w-5 h-5 flex items-center justify-center rounded-full text-[10px]">5</span>
                      Si tuvieras a un alumno aquí, ¿qué esperas que realice?
                    </label>
                    <button @click="abrirPopup('info', 5)" class="text-gray-400 hover:text-[#00A859] transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></button>
                    <button @click="abrirPopup('ejemplo', 5)" class="text-gray-400 hover:text-[#99CC33] transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg></button>
                  </div>
                  <textarea v-model="seleccion.expectativasAlumno" class="input-style h-24" placeholder="Ej: Que investigue herramientas gratuitas y proponga un prototipo sencillo..."></textarea>
                </div>

              </div>
            </section>
          </div>
        </transition>

        <transition name="fade" mode="out-in">
          <div v-if="pasoActual === 3" class="space-y-8 animate-in slide-in-from-bottom-4 duration-500">
            <section class="bg-white rounded-[2.5rem] p-8 md:p-10 border border-gray-100 shadow-[0_20px_50px_rgb(0,0,0,0.05)] relative overflow-hidden">
              <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-6 mb-8 mt-4 xl:mt-0">
                <div class="flex items-center gap-4">
                  <div class="w-12 h-12 rounded-2xl bg-[#00A859]/10 flex items-center justify-center text-[#00A859]">
                     <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                  </div>
                  <h2 class="text-2xl font-black uppercase tracking-tight text-[#1F2937]">Match Académico</h2>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                  <button @click="esInfoSimulada = !esInfoSimulada"
                    :class="esInfoSimulada ? 'bg-[#1F2937] text-white border-[#1F2937] shadow-md' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-200 shadow-sm'"
                    class="px-5 py-2.5 rounded-full font-bold text-xs tracking-widest uppercase transition-all flex items-center gap-2 border">
                    <svg v-if="esInfoSimulada" class="w-4 h-4 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Información Simulada
                  </button>
                  <button @click="cargarDemo" :disabled="esModoDemo" 
                    :class="esModoDemo ? 'bg-[#00A859]/10 text-[#00A859] border-[#00A859]/20 cursor-default' : 'bg-white text-[#00A859] hover:bg-gray-50 border-gray-200 hover:border-[#00A859] shadow-sm'"
                    class="px-5 py-2.5 rounded-full font-bold text-xs tracking-widest uppercase transition-all flex items-center gap-2 border">
                    <span v-if="esModoDemo">✓ DEMO ACTIVA</span>
                    <span v-else> Cargar Demo</span>
                  </button>
                  <button @click="limpiarFormulario" class="px-5 py-2.5 bg-white text-red-500 hover:bg-red-50 hover:border-red-500 border border-gray-200 rounded-full font-bold text-xs tracking-widest uppercase transition-all flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Vaciar
                  </button>
                </div>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="col-span-2 md:col-span-1">
                  <label class="label-style">Familia Profesional Asociada *</label>
                  <select v-model="seleccion.familia" class="input-style">
                    <option value="">Selecciona Familia...</option>
                    <option v-for="f in familiasFiltradas" :key="f" :value="f">{{ f }}</option>
                  </select>
                </div>

                <div class="col-span-2 md:col-span-1">
                  <label class="label-style">Nivel del Grupo-Clase *</label>
                  <select v-model="seleccion.nivelGrupo" class="input-style">
                    <option value="Básico">Básico (Ej: FP Básica o 1º de GM)</option>
                    <option value="Medio">Medio (Ej: 2º de GM o 1º de GS)</option>
                    <option value="Alto">Alto (Ej: 2º de GS o Especialización)</option>
                  </select>
                </div>

                <div class="col-span-2 mt-4">
                  <label class="label-style !mb-4">Ciclo Formativo Implicado *</label>
                  
                  <div v-if="ciclos.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                     <button v-for="c in ciclos" :key="c.id" 
                        @click="seleccion.cicloId = c.id"
                        :class="seleccion.cicloId === c.id 
                            ? 'bg-gradient-to-r from-[#00A859] to-[#99CC33] border-transparent text-white' 
                            : 'bg-[#1F2937] border-transparent text-gray-300 hover:border-[#00A859]/40'"
                        class="text-left px-5 py-4 rounded-2xl border-2 transition-all duration-200 flex items-center gap-3 relative overflow-hidden group shadow-sm">
                        
                        <div class="shrink-0 transition-transform" :class="seleccion.cicloId === c.id ? 'scale-100' : 'scale-0 opacity-0 hidden'">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        
                        <div class="flex-1">
                            <span class="text-sm font-bold leading-tight">{{ c.nombre }}</span>
                        </div>
                     </button>
                  </div>
                  <div v-else class="bg-gray-50 border border-dashed border-gray-200 rounded-3xl p-6 text-center">
                      <p class="text-gray-500 text-sm">Selecciona una Familia Profesional para ver sus ciclos.</p>
                  </div>
                </div>

                <div class="col-span-2 mt-6">
                  <label class="label-style !mb-3">Curso del Alumnado *</label>
                  <div class="flex bg-[#1F2937] p-1.5 rounded-2xl w-full md:w-1/2 shadow-inner">
                    <button @click="seleccion.cursoSeleccionado = 1" 
                      :class="seleccion.cursoSeleccionado === 1 ? 'bg-[#374151] text-white shadow font-black' : 'text-gray-400 hover:text-white'" 
                      class="flex-1 py-3 rounded-xl text-sm transition-all">
                      1º Curso
                    </button>
                    <button @click="seleccion.cursoSeleccionado = 2" 
                      :class="seleccion.cursoSeleccionado === 2 ? 'bg-[#374151] text-white shadow font-black' : 'text-gray-400 hover:text-white'" 
                      class="flex-1 py-3 rounded-xl text-sm transition-all">
                      2º Curso
                    </button>
                  </div>
                </div>

                <div class="col-span-2 mt-2 pt-6 border-t border-gray-100">
                  <div class="w-full bg-gray-50 p-6 rounded-3xl border border-gray-200 relative">
                    <div class="flex items-center justify-between mb-3">
                      <label class="label-style !mb-0 !ml-0">Forzar Módulo Específico (Opcional)</label>
                      <span class="text-xs text-gray-400 italic">Si no eliges, la IA cruzará con todos.</span>
                    </div>
                    
                    <select v-model="modulosSeleccionados" multiple :disabled="modulosDelCurso.length === 0" class="input-style min-h-[120px]">
                      <option v-for="m in modulosDelCurso" :key="m.id" :value="m.id">{{ m.nombre }}</option>
                    </select>
                    <p v-if="modulosDelCurso.length === 0 && seleccion.cicloId" class="text-xs text-red-500 mt-2 italic">No hay módulos cargados para este curso.</p>
                  </div>
                </div>

                <div class="col-span-2 mt-2 pt-6 border-t border-gray-100">
                  <div class="w-full md:w-1/3">
                    <label class="label-style !mb-3 text-[#1F2937]">Cantidad de Variantes a Generar *</label>
                    <select v-model="seleccion.cantidadMicroretos" class="input-style focus:!border-[#00A859]">
                      <option v-for="n in [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15]" :key="n" :value="n">
                        Generar {{ n }} Variante{{ n > 1 ? 's' : '' }}
                      </option>
                    </select>
                    <p class="text-xs text-gray-500 mt-2 italic pl-4">La IA diseñará enfoques distintos para el mismo problema.</p>
                  </div>
                </div>

              </div>
            </section>
          </div>
        </transition>

      </main>

      <div class="mt-16 flex flex-col items-center gap-6">
        
        <div v-if="pasoActual === totalPasos && !crmActualizado" class="bg-yellow-50 border border-yellow-200 shadow-sm rounded-2xl p-5 flex items-start md:items-center gap-4 w-full max-w-4xl animate-in slide-in-from-bottom-2">
          <div class="bg-yellow-400 text-white p-2 rounded-full shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          </div>
          <div class="flex-1">
            <h4 class="text-yellow-800 font-black text-sm uppercase tracking-widest mb-1">¡No pierdas tu trabajo!</h4>
            <p class="text-yellow-900 text-sm font-medium">Si has modificado el Sector, el Tamaño o el contexto del Problema, haz clic en <strong class="font-black">Actualizar Empresa</strong> antes de generar el reto para guardarlo en la base de datos.</p>
          </div>
        </div>

        <div class="flex flex-wrap md:flex-nowrap gap-4 w-full max-w-4xl">
          <button v-if="pasoActual > 1" @click="retrocederPaso" class="flex-1 min-w-[150px] px-10 py-6 bg-white text-[#1F2937] border-2 border-gray-200 rounded-full font-black text-xs tracking-widest transition-all hover:bg-gray-50 hover:border-gray-300 active:scale-95">
            VOLVER
          </button>
          
          <button v-if="pasoActual < totalPasos" @click="avanzarPaso" 
            :disabled="(pasoActual === 1 && !paso1Valido) || (pasoActual === 2 && !paso2Valido)"
            class="flex-[2] min-w-[200px] px-10 py-6 bg-gradient-to-r from-[#00A859] to-[#99CC33] text-white rounded-full font-black text-xs tracking-widest shadow-md hover:shadow-lg disabled:opacity-30 transition-all hover:scale-105 active:scale-95">
            SIGUIENTE PASO
          </button>

          <button v-if="pasoActual === totalPasos" @click="guardarInfoEmpresa" :disabled="actualizandoCRM || crmActualizado"
            class="flex-1 min-w-[200px] px-6 py-6 border-2 text-[#1F2937] bg-white border-gray-200 hover:border-[#00A859] hover:text-[#00A859] rounded-full font-black text-xs tracking-widest transition-all active:scale-95 flex items-center justify-center gap-2"
            :class="crmActualizado ? '!border-[#00A859] !bg-[#00A859]/5 !text-[#00A859]' : ''">
            <template v-if="actualizandoCRM">
              <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2v4a6 6 0 106 6h4a10 10 0 11-10-10z"/></svg>
              GUARDANDO...
            </template>
            <template v-else-if="crmActualizado">
              <span class="text-[#00A859] flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                INFO GUARDADA
              </span>
            </template>
            <template v-else>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
              {{ seleccion.empresaId ? 'ACTUALIZAR EMPRESA' : 'GUARDAR EMPRESA' }}
            </template>
          </button>

          <button v-if="pasoActual === totalPasos" @click="generarReto" :disabled="!paso3Valido || cargando"
            class="flex-[2] min-w-[250px] px-8 py-6 bg-gradient-to-r from-[#00A859] to-[#99CC33] text-white rounded-full font-black text-lg shadow-[0_10px_30px_rgba(0,168,89,0.3)] hover:shadow-[0_15px_40px_rgba(153,204,51,0.4)] transition-all hover:-translate-y-1 active:scale-95 disabled:opacity-40 flex items-center justify-center gap-3">
            <template v-if="!cargando">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
              <span class="uppercase tracking-wide">{{ microretosGenerados.length > 0 ? 'Generar más variantes' : 'Procesar con IA' }}</span>
            </template>
            <template v-else>
              <div class="w-5 h-5 border-3 border-white/30 border-t-white rounded-full animate-spin"></div>
              <span class="text-sm">GENERANDO {{ seleccion.cantidadMicroretos }} RETO(S)...</span>
            </template>
          </button>
        </div>
      </div>

      <div v-if="microretosGenerados.length > 0" class="mt-24 pb-20 space-y-32 font-sans relative">
        
        <div v-if="microretosGenerados.length > 1" class="flex justify-center md:justify-end mb-8 sticky top-4 z-50">
            <button @click="guardarTodos" :disabled="todosGuardados || guardandoTodos" class="px-8 py-4 bg-white border-2 border-gray-200 text-[#1F2937] hover:border-[#00A859] hover:text-[#00A859] rounded-full font-black text-xs md:text-sm uppercase tracking-widest shadow-lg transition-all hover:-translate-y-1 active:scale-95 disabled:hover:translate-y-0 disabled:opacity-80 flex items-center gap-2">
                <template v-if="guardandoTodos">
                  <svg class="animate-spin w-5 h-5" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2v4a6 6 0 106 6h4a10 10 0 11-10-10z"/></svg> 
                  GUARDANDO TODOS...
                </template>
                <template v-else-if="todosGuardados">
                  <svg class="w-5 h-5 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                  ✓ TODOS GUARDADOS
                </template>
                <template v-else>
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                  GUARDAR LOS {{ microretosGenerados.length }} RETOS
                </template>
            </button>
        </div>

        <div v-for="(reto, index) in microretosGenerados" :key="index" class="relative animate-in slide-in-from-bottom-8 duration-700">
          
          <div v-if="microretosGenerados.length > 1" class="absolute -top-16 left-0 w-full flex items-center justify-center z-10">
            <div class="w-full h-[1px] bg-gradient-to-r from-transparent via-gray-300 to-transparent"></div>
            <span class="absolute px-6 py-2 bg-white border border-gray-200 text-gray-500 rounded-full text-xs font-black uppercase tracking-widest shadow-sm">
              Variante #{{ microretosGenerados.length - index }}
            </span>
          </div>

          <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mb-8 gap-4">
            <div>
              <h3 class="text-lg font-black text-[#1F2937]">Previsualización de la Ficha Técnica</h3>
              <p class="text-sm text-gray-500">Formato oficial de archivo.</p>
            </div>
            <button @click="guardar(index)" :disabled="reto._ui_guardado || reto._ui_guardando" class="btn-save w-full md:w-auto" :class="reto._ui_guardado ? 'border-[#00A859] bg-[#00A859]/5 text-[#00A859]' : ''">
              <span v-if="reto._ui_guardando" class="flex items-center gap-2"><svg class="animate-spin w-4 h-4" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2v4a6 6 0 106 6h4a10 10 0 11-10-10z"/></svg> GUARDANDO...</span>
              <span v-else-if="reto._ui_guardado">✓ PUBLICADO EN BD</span>
              <span v-else>GUARDAR ESTA VERSIÓN</span>
            </button>
          </div>
          
          <div class="bg-white rounded-[2rem] shadow-[0_20px_50px_rgb(0,0,0,0.06)] mx-auto max-w-5xl overflow-hidden border border-gray-100 relative z-20">
            <div class="bg-gray-50 border-b border-gray-100 p-10 md:px-16 pt-12">
              <p class="text-[#00A859] font-bold text-[10px] tracking-[0.2em] uppercase mb-4 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                DuaLab · Ficha de Microreto
              </p>
              <h1 class="text-3xl md:text-5xl font-black text-[#1F2937] tracking-tight leading-tight mb-2">
                {{ reto.titulo }}
              </h1>
              <h2 class="text-lg md:text-xl text-gray-500 font-medium leading-relaxed mb-8">
                {{ reto.subtitulo }}
              </h2>
              
              <div class="flex flex-wrap gap-3">
                <span class="flex items-center gap-2 px-4 py-2 bg-[#1F2937] text-white rounded-lg text-xs font-bold uppercase tracking-wider shadow-sm">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                  {{ reto.empresa_nombre }}
                </span>
                <span class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-[#1F2937] rounded-lg text-xs font-bold uppercase tracking-wider">
                  <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2-2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                  {{ seleccion.familia }}
                </span>
                <span class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-500 rounded-lg text-xs font-bold uppercase tracking-wider">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                  Nivel: {{ seleccion.nivelGrupo }} ({{ seleccion.cursoSeleccionado }}º)
                </span>
              </div>
            </div>

            <div class="p-10 md:p-16 space-y-12">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <div>
                  <h3 class="flex items-center gap-2 text-[#00A859] font-bold uppercase text-xs tracking-widest border-b border-gray-100 pb-2 mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                    ¿Quién es {{ reto.empresa_nombre }}?
                  </h3>
                  <p class="text-gray-600 text-sm leading-relaxed">{{ reto.quien_es }}</p>
                </div>
                <div>
                  <h3 class="flex items-center gap-2 text-[#00A859] font-bold uppercase text-xs tracking-widest border-b border-gray-100 pb-2 mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Su día a día
                  </h3>
                  <p class="text-gray-600 text-sm leading-relaxed">{{ reto.dia_a_dia }}</p>
                </div>
              </div>

              <div>
                <h3 class="flex items-center gap-2 text-yellow-600 font-bold uppercase text-xs tracking-widest border-b border-gray-100 pb-2 mb-4">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                  Dificultades
                </h3>
                <ul class="space-y-2 pl-2">
                  <li v-for="(item, i) in reto.dificultades" :key="i" class="flex items-start gap-3 text-sm text-gray-700">
                    <span class="text-yellow-500 font-black mt-0.5">•</span><span>{{ item }}</span>
                  </li>
                </ul>
              </div>

              <div class="bg-gradient-to-r from-gray-50 to-white border-l-4 border-[#00A859] p-8 rounded-r-2xl shadow-sm border-y border-r border-gray-100">
                <h3 class="text-[#00A859] font-black uppercase text-[10px] tracking-[0.2em] mb-2 flex items-center gap-2">Pregunta del Reto</h3>
                <p class="text-xl md:text-2xl font-bold text-[#1F2937] leading-snug">{{ reto.pregunta_reto }}</p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <div>
                  <h3 class="flex items-center gap-2 text-[#00A859] font-bold uppercase text-xs tracking-widest border-b border-gray-100 pb-2 mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Qué necesitan
                  </h3>
                  <ul class="space-y-2 pl-2">
                    <li v-for="(item, i) in reto.que_necesitan" :key="i" class="flex items-start gap-3 text-sm text-gray-700">
                      <span class="text-[#00A859] font-black mt-0.5">•</span><span>{{ item }}</span>
                    </li>
                  </ul>
                </div>
                <div>
                  <h3 class="flex items-center gap-2 text-red-500 font-bold uppercase text-xs tracking-widest border-b border-gray-100 pb-2 mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    Limitaciones
                  </h3>
                  <ul class="space-y-2 pl-2">
                    <li v-for="(item, i) in reto.limitaciones" :key="i" class="flex items-start gap-3 text-sm text-gray-700">
                      <span class="text-red-500 font-black mt-0.5">•</span><span>{{ item }}</span>
                    </li>
                  </ul>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <div>
                  <h3 class="flex items-center gap-2 text-[#00A859] font-bold uppercase text-xs tracking-widest border-b border-gray-100 pb-2 mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    Ejemplos de Prototipos
                  </h3>
                  <ul class="space-y-2 pl-2">
                    <li v-for="(item, i) in reto.prototipos" :key="i" class="flex items-start gap-3 text-sm text-gray-700">
                      <span class="text-[#00A859] font-black mt-0.5">•</span><span>{{ item }}</span>
                    </li>
                  </ul>
                </div>
                <div>
                  <h3 class="flex items-center gap-2 text-blue-600 font-bold uppercase text-xs tracking-widest border-b border-gray-100 pb-2 mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    ODS Sugeridos
                  </h3>
                  <ul class="space-y-2 pl-2">
                    <li v-for="ods in reto.ods_sugeridos" :key="ods" class="text-sm font-semibold text-[#1F2937]">{{ ods }}</li>
                  </ul>
                </div>
              </div>

              <div class="pt-6">
                <h3 class="flex items-center gap-2 text-[#1F2937] font-bold uppercase text-xs tracking-widest border-b-2 border-gray-200 pb-2 mb-6">
                  <svg class="w-5 h-5 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                  RA/CE Seleccionados
                </h3>
                
                <div class="space-y-6">
                  <div v-for="evalObj in reto.evaluacion_oficial" :key="evalObj.modulo" class="bg-white border border-gray-200 p-6 rounded-2xl shadow-sm">
                    <p class="text-xs uppercase font-bold text-gray-400 mb-1">Módulo</p>
                    <p class="font-black text-[#1F2937] text-lg mb-4">{{ evalObj.modulo }}</p>
                    <div class="mb-4">
                      <p class="text-xs uppercase font-bold text-[#00A859] mb-1 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/></svg> Resultado de Aprendizaje
                      </p>
                      <p class="text-sm font-semibold text-gray-700 bg-gray-50 p-4 rounded-xl border border-gray-100">{{ evalObj.ra }}</p>
                    </div>
                    <div class="mb-4">
                      <p class="text-xs uppercase font-bold text-gray-500 mb-2">Criterios de Evaluación:</p>
                      <ul class="space-y-2">
                        <li v-for="(ce, i) in evalObj.ce" :key="i" class="text-sm text-gray-600 flex items-start gap-2">
                          <span class="text-[#00A859] font-bold mt-0.5">✓</span> {{ ce }}
                        </li>
                      </ul>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                      <p class="text-sm text-gray-500 italic"><span class="font-bold not-italic text-[#1F2937]">Aplicación:</span> {{ evalObj.aplicacion }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <div v-if="reto.variantes && reto.variantes.length > 0" class="pt-6">
                <h3 class="flex items-center gap-2 text-[#00A859] font-bold uppercase text-xs tracking-widest border-b border-gray-100 pb-2 mb-4">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                  Variantes
                </h3>
                <ul class="space-y-3">
                  <li v-for="(varItem, i) in reto.variantes" :key="i" class="text-sm text-gray-700 bg-gray-50 border border-gray-200 p-4 rounded-xl shadow-sm">
                    <strong v-if="varItem.includes(':')" class="text-[#1F2937] block mb-1">{{ varItem.split(':')[0] }}</strong>
                    <span>{{ varItem.includes(':') ? varItem.substring(varItem.indexOf(':') + 1).trim() : varItem }}</span>
                  </li>
                </ul>
              </div>
            </div>
          </div> 
          
          <div class="bg-gray-50 text-[#1F2937] p-10 md:p-16 rounded-[2rem] shadow-sm mx-auto max-w-5xl border border-gray-200 relative mt-12">
            <div class="absolute top-0 right-0 bg-white border-b border-l border-gray-200 text-gray-500 px-6 py-2 font-bold text-xs tracking-widest uppercase rounded-bl-xl shadow-sm">Uso Exclusivo Docente</div>
            <h2 class="text-2xl font-black text-[#1F2937] mb-2 mt-2 flex items-center gap-3">
              <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
              Guía de Implementación
            </h2>
            <p class="text-gray-500 text-sm mb-12">Recomendaciones pedagógicas para dinamizar el reto.</p>
            
            <div class="grid grid-cols-1 gap-6">
              <div v-for="(tip, i) in reto.tips_profesorado" :key="i" class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                <div class="text-sm text-gray-700 leading-relaxed">
                  <strong v-if="tip.includes(':')" class="text-[#00A859] block mb-2 uppercase tracking-wider text-xs">
                    <svg class="w-4 h-4 inline-block mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path v-if="i===0" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                      <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ tip.split(':')[0] }}
                  </strong>
                  <span class="block text-gray-600">{{ tip.includes(':') ? tip.substring(tip.indexOf(':') + 1).trim() : tip }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <transition name="fade">
        <div v-if="popupActivo" class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="cerrarPopup">
          <div class="bg-white rounded-3xl p-8 max-w-lg w-full shadow-2xl border border-gray-100 relative">
            <button @click="cerrarPopup" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 bg-gray-50 hover:bg-gray-100 p-2 rounded-full transition-colors"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            <div class="flex items-center gap-3 mb-6">
              <div :class="popupActivo.tipo === 'info' ? 'bg-[#00A859]/10 text-[#00A859]' : 'bg-[#99CC33]/20 text-[#00A859]'" class="p-3 rounded-2xl">
                <svg v-if="popupActivo.tipo === 'info'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
              </div>
              <h3 class="text-xl font-black uppercase tracking-tight text-[#1F2937]">{{ popupActivo.tipo === 'info' ? 'Ayuda' : 'Ejemplo' }}</h3>
            </div>
            <p class="text-gray-600 text-base md:text-lg leading-relaxed bg-gray-50 p-5 rounded-2xl border border-gray-100">{{ ayudas[popupActivo.id][popupActivo.tipo] }}</p>
          </div>
        </div>
      </transition>

    </div>
  </div>
</template>

<style scoped>
@reference "../style.css";

/* Estilos adaptados: Inputs OSCUROS sobre fondo claro para alto contraste */
.input-style {
  @apply w-full bg-[#1F2937] border-2 border-transparent rounded-2xl p-4 text-sm font-semibold text-white focus:bg-[#273344] focus:border-[#00A859] focus:ring-4 focus:ring-[#00A859]/20 outline-none transition-all placeholder:text-gray-500 disabled:opacity-50 shadow-inner;
}

.label-style {
  @apply text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 ml-4 mb-2 block;
}

.btn-save {
  @apply px-8 py-4 bg-white border-2 border-gray-200 text-[#1F2937] rounded-full font-black text-xs uppercase tracking-widest shadow-sm transition-all hover:-translate-y-1 hover:border-[#00A859] hover:text-[#00A859] active:scale-95 disabled:hover:translate-y-0 disabled:opacity-50;
}

/* Animaciones más suaves acordes a una interfaz clara */
.fade-enter-active, .fade-leave-active { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.fade-enter-from { opacity: 0; transform: translateX(10px); }
.fade-leave-to { opacity: 0; transform: translateX(-10px); }
.fade-up-enter-active { transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1); }
.fade-up-enter-from { opacity: 0; transform: translateY(40px); }

.dropdown-enter-active, .dropdown-leave-active {
  transition: all 0.15s ease;
}
.dropdown-enter-from, .dropdown-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>