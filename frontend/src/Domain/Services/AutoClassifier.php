<?php
// C:\xampp\htdocs\SistemIncidencia\frontend\src\Domain\Services\AutoClassifier.php

namespace App\Domain\Services;

class AutoClassifier {
    public static function classify(string $text): array {
        $text = mb_strtolower($text, 'UTF-8');
        
        // Subcategories matching keywords
        $rules = [
            // Proyector (ID 1)
            1 => ['proyector', 'ecran', 'hdmi', 'pizarra', 'pantalla gigante', 'lente', 'resolucion', 'no prende proyector', 'vga'],
            
            // PC Docente (ID 2)
            2 => ['pc', 'computadora', 'computador', 'docente', 'cpu', 'teclado', 'mouse', 'monitor', 'pantalla', 'parlante', 'parlantes', 'auricular', 'microfono'],
            
            // Sin Internet (ID 3)
            3 => ['internet', 'wifi', 'red', 'cable de red', 'no conecta', 'sin conexion', 'desconectado', 'paginas', 'navegar', 'offline'],
            
            // Lento (ID 4)
            4 => ['lento', 'lentitud', 'tarda', 'demora', 'congelado', 'pegado', 'se cuelga', 'colgo', 'procesador', 'memoria', 'demora en cargar'],
            
            // Aire Acondicionado (ID 5)
            5 => ['aire', 'acondicionado', 'calor', 'frio', 'temperatura', 'ventilador', 'clima', 'control de aire', 'gotea', 'no enfria'],
            
            // Luz fundida (ID 6)
            6 => ['luz', 'foco', 'fluorescente', 'lampara', 'oscuro', 'iluminacion', 'apagado', 'electricidad', 'corto']
        ];
        
        // Priority matching keywords
        $prioRules = [
            3 => ['corto', 'urgente', 'urgencia', 'examen', 'parcial', 'final', 'no prende', 'sin internet', 'apagado', 'clase', 'sin luz'], // Alta (3)
            2 => ['calor', 'lento', 'wifi', 'mouse', 'teclado', 'no enfria'], // Media (2)
            1 => ['parlante', 'foco', 'lampara'] // Baja (1)
        ];

        // Match Subcategory
        $bestSubId = 2; // Default to PC Docente
        $maxSubMatches = 0;
        
        foreach ($rules as $subId => $keywords) {
            $matches = 0;
            foreach ($keywords as $kw) {
                if (strpos($text, $kw) !== false) {
                    $matches++;
                }
            }
            if ($matches > $maxSubMatches) {
                $maxSubMatches = $matches;
                $bestSubId = $subId;
            }
        }
        
        // Match Priority
        $bestPrioId = 2; // Default to Media
        $maxPrioMatches = 0;
        foreach ($prioRules as $prioId => $keywords) {
            $matches = 0;
            foreach ($keywords as $kw) {
                if (strpos($text, $kw) !== false) {
                    $matches++;
                }
            }
            if ($matches > $maxPrioMatches) {
                $maxPrioMatches = $matches;
                $bestPrioId = $prioId;
            }
        }
        
        return [
            'subcategoria_id' => $bestSubId,
            'prioridad_id' => $bestPrioId,
            'has_matches' => ($maxSubMatches > 0 || $maxPrioMatches > 0)
        ];
    }
}
