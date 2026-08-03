<?php

namespace App\Services;

use Illuminate\Support\Str;

class ImportParsingService
{
    public function parse(string $rawText): array
    {
        if (str_contains($rawText, "\t")) {
            return $this->parseHorizontal($rawText);
        } else {
            return $this->parseVerticalChunk($rawText);
        }
    }

    private function parseHorizontal(string $rawText): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $rawText);
        $parsedData = [];
        
        // --- MEMORIA DE RELLENO INTELIGENTE ---
        $lastDr = null;
        $lastProvider = null;
        $lastOcGroup = null;
        $lastInvoice = null;

        foreach ($lines as $line) {
            // Limpieza
            $line = str_replace(["\xC2\xA0", "\xA0"], " ", $line);
            $line = rtrim($line, "\r\n\t "); 
            
            if (trim($line) === '') continue;

            $columns = explode("\t", $line);
            if (count($columns) < 3) continue;

            // Filtros de Cabecera
            $col0 = strtoupper(trim($columns[0] ?? ''));
            $col1 = strtoupper(trim($columns[1] ?? ''));
            if (str_contains($col0, 'DR') && str_contains($col1, 'PROVEEDOR')) continue;
            if (str_contains($col0, 'MARINA') || str_contains($col1, 'VIAJE')) continue;

            $row = $this->mapColumns($columns);
            
            // Paso 1: Detectar si tiene Dinero (Es un item cobrable)
            $hasMoney = !empty($row['total_raw']) || !empty($row['value_raw']);
            
            // Paso 2: Gestión de DR
            if (!empty($row['dr_raw'])) {
                $lastDr = $row['dr_raw']; // Si tiene DR propio, se respeta y se memoriza
            } elseif ($hasMoney && !empty($lastDr)) {
                $row['dr_raw'] = $lastDr; // Si tiene dinero pero no DR, hereda el de arriba
            }

            // Paso 3: Relleno de datos administrativos (Hereda si viene vacío)
            if (empty($row['supplier_raw']) && !empty($lastProvider)) $row['supplier_raw'] = $lastProvider;
            elseif (!empty($row['supplier_raw'])) $lastProvider = $row['supplier_raw'];

            if (empty($row['oc_group_raw']) && !empty($lastOcGroup)) $row['oc_group_raw'] = $lastOcGroup;
            elseif (!empty($row['oc_group_raw'])) $lastOcGroup = $row['oc_group_raw'];

            if (empty($row['invoice_provider_raw']) && !empty($lastInvoice)) $row['invoice_provider_raw'] = $lastInvoice;
            elseif (!empty($row['invoice_provider_raw'])) $lastInvoice = $row['invoice_provider_raw'];

            // --- FILTRO DE BASURA ---
            // Se elimina SOLO SI no tiene DR y no tiene Dinero
            if (empty($row['dr_raw']) && !$hasMoney) {
                continue; 
            }
            
            $row['needs_invoice'] = $this->checkIfNeedsInvoice($row['oc_group_raw']);
            
            $parsedData[] = $row;
        }

        return $parsedData;
    }

    private function parseVerticalChunk(string $rawText): array
    {
        return []; 
    }

    private function mapColumns(array $columns): array
    {
        $columns = array_pad($columns, 13, null);

        return [
            '_temp_id' => Str::uuid()->toString(),
            'dr_raw' => $this->clean($columns[0]),
            'supplier_raw' => $this->clean($columns[1]),
            'oc_group_raw' => $this->clean($columns[2]),
            'description_raw' => $this->clean($columns[3]),
            'cotizacion_raw' => $this->clean($columns[4]),
            'oci_ms_raw' => $this->clean($columns[5]),
            'anticipo_raw' => $this->clean($columns[6]),
            'invoice_provider_raw' => $this->clean($columns[7]),
            'value_raw' => $this->cleanMoney($columns[8]),      
            'other_costs_raw' => $this->cleanMoney($columns[9]),
            'freight_raw' => $this->cleanMoney($columns[10]),   
            'total_raw' => $this->cleanMoney($columns[11]),     
            'system_origin' => $this->clean($columns[12]),
            'needs_invoice' => false, 
        ];
    }

    private function clean($val): ?string
    {
        if (is_null($val)) return null;
        $val = str_replace(["\xC2\xA0", "\xA0"], " ", $val);
        $val = preg_replace('/\s+/', ' ', trim($val));
        if ($val === '') return null;
        return ($val === '-' || $val === '.') ? null : $val;
    }

    private function cleanMoney($val): ?string
    {
        if (is_null($val)) return null;
        $val = preg_replace('/[\s\p{Z}]+/u', '', $val);
        if ($val === '' || $val === '-' || $val === 'X' || $val === '$' || $val === '$-') return null;
        return $val;
    }

    private function checkIfNeedsInvoice(?string $ocGroup): bool
    {
        if (empty($ocGroup)) return false;
        $targetCompanies = config('nexus.invoice_triggers', []); 
        foreach ($targetCompanies as $t) {
            if (str_contains(strtoupper($ocGroup), $t)) return true;
        }
        return false;
    }
}