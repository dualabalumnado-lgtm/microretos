import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  stages: [
    { duration: '30s', target: 20 }, // Carga normal: 20 usuarios
    { duration: '1m', target: 50 },  // Pico de carga: 50 usuarios
    { duration: '30s', target: 0 },  // Bajada
  ],
};

export default function () {
  const url = 'https://tu-api.com/api/upload';
  const payload = { 
    tipo: 'documento', 
    microproyecto_uuid: 'test-uuid' 
  };
  
  // Nota: Subir archivos grandes en carga puede saturar tu ancho de banda
  const res = http.post(url, payload);
  
  check(res, { 'status es 201 o 422': (r) => r.status === 201 || r.status === 422 });
  sleep(1);
}