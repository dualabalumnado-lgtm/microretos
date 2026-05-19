<?php

return [
    /*
     * Días que un elemento puede permanecer en la papelera antes de ser
     * eliminado permanentemente por el job automático.
     * Configurable vía PAPELERA_DIAS_RETENCION en el .env.
     */
    'dias_retencion' => (int) env('PAPELERA_DIAS_RETENCION', 30),
];
