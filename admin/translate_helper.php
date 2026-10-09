<?php
/**
 * Helper de traducción automática usando MyMemory API (Gratuita)
 * Límite: 5000 palabras/día
 */

function auto_translate($text, $target_lang = 'en') {
    // Si el texto está vacío, retornar vacío
    if (empty(trim($text))) {
        return '';
    }
    
    // Si el texto ya está en el idioma target, no traducir
    if (strlen($text) < 3) {
        return $text;
    }
    
    $source_lang = 'es';
    $url = "https://api.mymemory.translated.net/get?q=" . urlencode($text) . "&langpair={$source_lang}|{$target_lang}";
    
    try {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout de 5 segundos
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            
            if (isset($data['responseData']['translatedText'])) {
                $translated = $data['responseData']['translatedText'];
                // Si la traducción es igual al original, retornar original
                return ($translated !== $text) ? $translated : $text;
            }
        }
    } catch (Exception $e) {
        // Si hay error, retornar texto original
        error_log("Translation error: " . $e->getMessage());
    }
    
    return $text; // Si falla, retorna el texto original
}

// Función para traducir múltiples campos a la vez
function translate_fields($data, $fields_to_translate, $target_lang = 'en') {
    foreach ($fields_to_translate as $field) {
        if (isset($data[$field]) && !empty($data[$field])) {
            $data[$field . '_en'] = auto_translate($data[$field], $target_lang);
        } else {
            $data[$field . '_en'] = '';
        }
    }
    return $data;
}
?>