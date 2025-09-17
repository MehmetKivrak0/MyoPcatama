<?php

/**
 * Türkçe Karakter Düzeltme Yardımcı Sınıfı
 * Excel import ve diğer işlemlerde Türkçe karakter sorunlarını çözer
 */
class TurkishCharacterHelper {
    
    /**
     * Türkçe karakter düzeltme
     * Excel'den gelen bozuk Türkçe karakterleri düzeltir
     */
    public static function fixTurkishCharacters($text) {
        if (empty($text)) {
            return $text;
        }
        
        // Excel'den gelen bozuk Türkçe karakterleri düzelt
        $replacements = [
            // Büyük harfler - Windows-1254'ten gelen bozuk karakterler
            'Ã‡' => 'Ç', 'Äž' => 'Ğ', 'Ä°' => 'İ', 'Ã–' => 'Ö', 'Åž' => 'Ş', 'Ãœ' => 'Ü',
            'Ã‡' => 'Ç', 'Ã‡' => 'Ç', 'Ã‡' => 'Ç',
            'Ä°' => 'İ', 'Ä°' => 'İ', 'Ä°' => 'İ',
            'Ã–' => 'Ö', 'Ã–' => 'Ö', 'Ã–' => 'Ö',
            'Ãœ' => 'Ü', 'Ãœ' => 'Ü', 'Ãœ' => 'Ü',
            'Åž' => 'Ş', 'Åž' => 'Ş', 'Åž' => 'Ş',
            'Äž' => 'Ğ', 'Äž' => 'Ğ', 'Äž' => 'Ğ',
            
            // Küçük harfler - Windows-1254'ten gelen bozuk karakterler
            'Ã§' => 'ç', 'ÄŸ' => 'ğ', 'Ä±' => 'ı', 'Ã¶' => 'ö', 'ÅŸ' => 'ş', 'Ã¼' => 'ü',
            'Ã§' => 'ç', 'Ã§' => 'ç', 'Ã§' => 'ç',
            'Ä±' => 'ı', 'Ä±' => 'ı', 'Ä±' => 'ı',
            'Ã¶' => 'ö', 'Ã¶' => 'ö', 'Ã¶' => 'ö',
            'Ã¼' => 'ü', 'Ã¼' => 'ü', 'Ã¼' => 'ü',
            'ÅŸ' => 'ş', 'ÅŸ' => 'ş', 'ÅŸ' => 'ş',
            'ÄŸ' => 'ğ', 'ÄŸ' => 'ğ', 'ÄŸ' => 'ğ',
            
            // Diğer yaygın bozukluklar
            'Ã‡' => 'Ç', 'Ã‡' => 'Ç', 'Ã‡' => 'Ç',
            'Ä°' => 'İ', 'Ä°' => 'İ', 'Ä°' => 'İ',
            'Ã–' => 'Ö', 'Ã–' => 'Ö', 'Ã–' => 'Ö',
            'Ãœ' => 'Ü', 'Ãœ' => 'Ü', 'Ãœ' => 'Ü',
            'Åž' => 'Ş', 'Åž' => 'Ş', 'Åž' => 'Ş',
            'Äž' => 'Ğ', 'Äž' => 'Ğ', 'Äž' => 'Ğ',
            
            // Küçük harf bozuklukları
            'Ã§' => 'ç', 'Ã§' => 'ç', 'Ã§' => 'ç',
            'Ä±' => 'ı', 'Ä±' => 'ı', 'Ä±' => 'ı',
            'Ã¶' => 'ö', 'Ã¶' => 'ö', 'Ã¶' => 'ö',
            'Ã¼' => 'ü', 'Ã¼' => 'ü', 'Ã¼' => 'ü',
            'ÅŸ' => 'ş', 'ÅŸ' => 'ş', 'ÅŸ' => 'ş',
            'ÄŸ' => 'ğ', 'ÄŸ' => 'ğ', 'ÄŸ' => 'ğ',
            
            // ISO-8859-9'dan gelen bozukluklar
            'Ã‡' => 'Ç', 'Äž' => 'Ğ', 'Ä°' => 'İ', 'Ã–' => 'Ö', 'Åž' => 'Ş', 'Ãœ' => 'Ü',
            'Ã§' => 'ç', 'ÄŸ' => 'ğ', 'Ä±' => 'ı', 'Ã¶' => 'ö', 'ÅŸ' => 'ş', 'Ã¼' => 'ü',
            
            // Diğer encoding sorunları
            'Ã‡' => 'Ç', 'Ã‡' => 'Ç', 'Ã‡' => 'Ç',
            'Ä°' => 'İ', 'Ä°' => 'İ', 'Ä°' => 'İ',
            'Ã–' => 'Ö', 'Ã–' => 'Ö', 'Ã–' => 'Ö',
            'Ãœ' => 'Ü', 'Ãœ' => 'Ü', 'Ãœ' => 'Ü',
            'Åž' => 'Ş', 'Åž' => 'Ş', 'Åž' => 'Ş',
            'Äž' => 'Ğ', 'Äž' => 'Ğ', 'Äž' => 'Ğ',
        ];
        
        // Bozuk karakterleri düzelt
        $text = strtr($text, $replacements);
        
        // UTF-8 encoding kontrolü ve düzeltme
        if (!mb_check_encoding($text, 'UTF-8')) {
            // Farklı encoding'leri dene
            $encodings = ['Windows-1254', 'ISO-8859-9', 'UTF-8'];
            foreach ($encodings as $encoding) {
                $converted = mb_convert_encoding($text, 'UTF-8', $encoding);
                if (mb_check_encoding($converted, 'UTF-8')) {
                    $text = $converted;
                    break;
                }
            }
        }
        
        return $text;
    }
    
    /**
     * İsim temizleme (fazla boşlukları kaldır, başlık durumu, Türkçe karakter düzeltme)
     */
    public static function cleanName($name) {
        if (empty($name)) {
            return $name;
        }
        
        // Türkçe karakterleri düzelt
        $name = self::fixTurkishCharacters($name);
        
        // Fazla boşlukları kaldır
        $name = preg_replace('/\s+/', ' ', trim($name));
        
        // Her kelimenin ilk harfini büyük yap (Türkçe uyumlu)
        $name = self::mb_ucwords($name);
        
        return $name;
    }
    
    /**
     * Türkçe karakterlere uygun başlık durumu (mb_ucwords alternatifi)
     */
    public static function mb_ucwords($string) {
        if (empty($string)) {
            return $string;
        }
        
        $words = explode(' ', $string);
        $result = [];
        
        foreach ($words as $word) {
            if (empty($word)) continue;
            
            // İlk harfi büyük yap
            $firstChar = mb_substr($word, 0, 1, 'UTF-8');
            $rest = mb_substr($word, 1, null, 'UTF-8');
            
            // Türkçe özel durumlar
            if ($firstChar === 'i' || $firstChar === 'I') {
                $firstChar = 'İ';
            } elseif ($firstChar === 'ı') {
                $firstChar = 'I';
            } else {
                $firstChar = mb_strtoupper($firstChar, 'UTF-8');
            }
            
            $result[] = $firstChar . mb_strtolower($rest, 'UTF-8');
        }
        
        return implode(' ', $result);
    }
    
    /**
     * Metin temizleme (genel kullanım için)
     */
    public static function cleanText($text) {
        if (empty($text)) {
            return $text;
        }
        
        // Türkçe karakterleri düzelt
        $text = self::fixTurkishCharacters($text);
        
        // Fazla boşlukları kaldır
        $text = preg_replace('/\s+/', ' ', trim($text));
        
        return $text;
    }
    
    /**
     * Türkçe karakter kontrolü
     */
    public static function hasTurkishCharacters($text) {
        return preg_match('/[ÇĞIİÖŞÜçğıiöşü]/', $text);
    }
    
    /**
     * Bozuk karakter kontrolü
     */
    public static function hasBrokenCharacters($text) {
        $brokenPatterns = [
            '/Ã‡/', '/Äž/', '/Ä°/', '/Ã–/', '/Åž/', '/Ãœ/',
            '/Ã§/', '/ÄŸ/', '/Ä±/', '/Ã¶/', '/ÅŸ/', '/Ã¼/'
        ];
        
        foreach ($brokenPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Encoding kontrolü ve düzeltme
     */
    public static function fixEncoding($text) {
        if (empty($text)) {
            return $text;
        }
        
        // UTF-8 kontrolü
        if (mb_check_encoding($text, 'UTF-8')) {
            return $text;
        }
        
        // Farklı encoding'leri dene
        $encodings = ['Windows-1254', 'ISO-8859-9', 'UTF-8', 'ASCII'];
        foreach ($encodings as $encoding) {
            $converted = mb_convert_encoding($text, 'UTF-8', $encoding);
            if (mb_check_encoding($converted, 'UTF-8')) {
                return $converted;
            }
        }
        
        // Son çare olarak UTF-8'e zorla çevir
        return mb_convert_encoding($text, 'UTF-8', 'auto');
    }
}
?>
