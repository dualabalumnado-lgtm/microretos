import axios from 'axios';

// Esta línea es la clave: 
// Si existe la variable de producción la usa, si no, usa '/api' para local
const apiURL = import.meta.env.VITE_API_URL || '/api';

const api = axios.create({
  baseURL: apiURL,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  }
});

export default api;