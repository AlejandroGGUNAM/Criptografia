<?php

if (!function_exists('app_crypto_storage_dir')) {
    function app_crypto_storage_dir()
    {
        return APP_BASE_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'crypto';
    }
}

if (!function_exists('app_crypto_key_path')) {
    function app_crypto_key_path()
    {
        return app_crypto_storage_dir() . DIRECTORY_SEPARATOR . 'app_aes_256.key';
    }
}

if (!function_exists('app_crypto_key')) {
    function app_crypto_key()
    {
        static $key = null;

        if (is_string($key)) {
            return $key;
        }

        $envKey = getenv('SERVICIO_AES256_KEY');
        if (is_string($envKey) && $envKey !== '') {
            $decoded = base64_decode($envKey, true);
            if ($decoded === false || strlen($decoded) !== 32) {
                throw new RuntimeException('SERVICIO_AES256_KEY debe ser una llave base64 de 32 bytes.');
            }

            $key = $decoded;
            return $key;
        }

        $dir = app_crypto_storage_dir();
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        $htaccess = $dir . DIRECTORY_SEPARATOR . '.htaccess';
        if (!is_file($htaccess)) {
            file_put_contents($htaccess, "Require all denied\n", LOCK_EX);
        }

        $path = app_crypto_key_path();
        if (!is_file($path)) {
            file_put_contents($path, 'key:v1:' . base64_encode(random_bytes(32)), LOCK_EX);
            @chmod($path, 0600);
        }

        $contents = trim((string) file_get_contents($path));
        if (!str_starts_with($contents, 'key:v1:')) {
            throw new RuntimeException('Formato de llave AES-256 no valido.');
        }

        $decoded = base64_decode(substr($contents, 7), true);
        if ($decoded === false || strlen($decoded) !== 32) {
            throw new RuntimeException('La llave AES-256 debe tener 32 bytes.');
        }

        $key = $decoded;
        return $key;
    }
}

if (!function_exists('app_encrypt')) {
    function app_encrypt($plaintext, $aad = '')
    {
        $plaintext = (string) $plaintext;
        $iv = random_bytes(12);
        [$ciphertext, $tag] = app_aes256_gcm_encrypt(app_crypto_key(), $iv, $plaintext, (string) $aad);

        return 'enc:v1:' . base64_encode($iv . $tag . $ciphertext);
    }
}

if (!function_exists('app_decrypt')) {
    function app_decrypt($ciphertext, $aad = '')
    {
        if (!is_string($ciphertext) || !str_starts_with($ciphertext, 'enc:v1:')) {
            return $ciphertext;
        }

        $packed = base64_decode(substr($ciphertext, 7), true);
        if ($packed === false || strlen($packed) < 28) {
            throw new RuntimeException('Texto cifrado AES-256-GCM no valido.');
        }

        $iv = substr($packed, 0, 12);
        $tag = substr($packed, 12, 16);
        $encrypted = substr($packed, 28);
        $plaintext = app_aes256_gcm_decrypt(app_crypto_key(), $iv, $encrypted, $tag, (string) $aad);

        if ($plaintext === false) {
            throw new RuntimeException('No fue posible autenticar el texto cifrado.');
        }

        return $plaintext;
    }
}

if (!function_exists('app_aes256_gcm_encrypt')) {
    function app_aes256_gcm_encrypt($key, $iv, $plaintext, $aad = '')
    {
        if (strlen($key) !== 32 || strlen($iv) !== 12) {
            throw new RuntimeException('AES-256-GCM requiere llave de 32 bytes e IV de 12 bytes.');
        }

        $hashSubkey = app_aes256_encrypt_block(str_repeat("\0", 16), $key);
        $j0 = $iv . "\0\0\0\1";
        $ciphertext = app_aes_gcm_ctr($key, app_aes_gcm_inc32($j0), $plaintext);
        $auth = app_aes_gcm_hash($hashSubkey, $aad, $ciphertext);
        $tag = app_xor_bytes(app_aes256_encrypt_block($j0, $key), $auth);

        return [$ciphertext, $tag];
    }
}

if (!function_exists('app_aes256_gcm_decrypt')) {
    function app_aes256_gcm_decrypt($key, $iv, $ciphertext, $tag, $aad = '')
    {
        if (strlen($key) !== 32 || strlen($iv) !== 12 || strlen($tag) !== 16) {
            return false;
        }

        $hashSubkey = app_aes256_encrypt_block(str_repeat("\0", 16), $key);
        $j0 = $iv . "\0\0\0\1";
        $auth = app_aes_gcm_hash($hashSubkey, $aad, $ciphertext);
        $expectedTag = app_xor_bytes(app_aes256_encrypt_block($j0, $key), $auth);

        if (!hash_equals($expectedTag, $tag)) {
            return false;
        }

        return app_aes_gcm_ctr($key, app_aes_gcm_inc32($j0), $ciphertext);
    }
}

if (!function_exists('app_aes_gcm_ctr')) {
    function app_aes_gcm_ctr($key, $counter, $input)
    {
        $output = '';
        $length = strlen($input);

        for ($offset = 0; $offset < $length; $offset += 16) {
            $block = substr($input, $offset, 16);
            $stream = app_aes256_encrypt_block($counter, $key);
            $output .= app_xor_bytes($block, substr($stream, 0, strlen($block)));
            $counter = app_aes_gcm_inc32($counter);
        }

        return $output;
    }
}

if (!function_exists('app_aes_gcm_inc32')) {
    function app_aes_gcm_inc32($block)
    {
        $prefix = substr($block, 0, 12);
        $counter = unpack('N', substr($block, 12, 4))[1];
        $counter = ($counter + 1) & 0xffffffff;

        return $prefix . pack('N', $counter);
    }
}

if (!function_exists('app_aes_gcm_hash')) {
    function app_aes_gcm_hash($hashSubkey, $aad, $ciphertext)
    {
        $data = app_gcm_pad($aad)
            . app_gcm_pad($ciphertext)
            . app_pack_uint64(strlen($aad) * 8)
            . app_pack_uint64(strlen($ciphertext) * 8);

        $y = str_repeat("\0", 16);
        $blocks = strlen($data) / 16;
        for ($i = 0; $i < $blocks; $i++) {
            $y = app_gcm_multiply(app_xor_bytes($y, substr($data, $i * 16, 16)), $hashSubkey);
        }

        return $y;
    }
}

if (!function_exists('app_gcm_pad')) {
    function app_gcm_pad($value)
    {
        $remainder = strlen($value) % 16;
        if ($remainder === 0) {
            return $value;
        }

        return $value . str_repeat("\0", 16 - $remainder);
    }
}

if (!function_exists('app_pack_uint64')) {
    function app_pack_uint64($value)
    {
        $high = intdiv($value, 0x100000000);
        $low = $value & 0xffffffff;

        return pack('N2', $high, $low);
    }
}

if (!function_exists('app_gcm_multiply')) {
    function app_gcm_multiply($x, $y)
    {
        $z = str_repeat("\0", 16);
        $v = $x;
        $r = "\xe1" . str_repeat("\0", 15);

        for ($i = 0; $i < 128; $i++) {
            if (app_get_bit($y, $i) === 1) {
                $z = app_xor_bytes($z, $v);
            }

            $lsb = ord($v[15]) & 1;
            $v = app_shift_right_one($v);
            if ($lsb === 1) {
                $v = app_xor_bytes($v, $r);
            }
        }

        return $z;
    }
}

if (!function_exists('app_get_bit')) {
    function app_get_bit($value, $index)
    {
        return (ord($value[intdiv($index, 8)]) >> (7 - ($index % 8))) & 1;
    }
}

if (!function_exists('app_shift_right_one')) {
    function app_shift_right_one($value)
    {
        $result = '';
        $carry = 0;

        for ($i = 0; $i < 16; $i++) {
            $byte = ord($value[$i]);
            $result .= chr(($byte >> 1) | $carry);
            $carry = ($byte & 1) ? 0x80 : 0;
        }

        return $result;
    }
}

if (!function_exists('app_xor_bytes')) {
    function app_xor_bytes($left, $right)
    {
        $length = strlen($left);
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= chr(ord($left[$i]) ^ ord($right[$i]));
        }

        return $result;
    }
}

if (!function_exists('app_aes256_encrypt_block')) {
    function app_aes256_encrypt_block($block, $key)
    {
        if (strlen($block) !== 16 || strlen($key) !== 32) {
            throw new RuntimeException('AES-256 requiere bloques de 16 bytes y llave de 32 bytes.');
        }

        $state = app_string_to_bytes($block);
        $roundKeys = app_aes256_round_keys($key);

        app_aes_add_round_key($state, $roundKeys[0]);

        for ($round = 1; $round < 14; $round++) {
            app_aes_sub_bytes($state);
            app_aes_shift_rows($state);
            app_aes_mix_columns($state);
            app_aes_add_round_key($state, $roundKeys[$round]);
        }

        app_aes_sub_bytes($state);
        app_aes_shift_rows($state);
        app_aes_add_round_key($state, $roundKeys[14]);

        return app_bytes_to_string($state);
    }
}

if (!function_exists('app_aes256_round_keys')) {
    function app_aes256_round_keys($key)
    {
        static $cache = [];

        $cacheKey = bin2hex($key);
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $sbox = app_aes_sbox();
        $rcon = [0x00, 0x01, 0x02, 0x04, 0x08, 0x10, 0x20, 0x40, 0x80];
        $bytes = app_string_to_bytes($key);
        $words = [];

        for ($i = 0; $i < 8; $i++) {
            $words[$i] = array_slice($bytes, $i * 4, 4);
        }

        for ($i = 8; $i < 60; $i++) {
            $temp = $words[$i - 1];

            if ($i % 8 === 0) {
                $temp = [$temp[1], $temp[2], $temp[3], $temp[0]];
                for ($j = 0; $j < 4; $j++) {
                    $temp[$j] = $sbox[$temp[$j]];
                }
                $temp[0] ^= $rcon[intdiv($i, 8)];
            } elseif ($i % 8 === 4) {
                for ($j = 0; $j < 4; $j++) {
                    $temp[$j] = $sbox[$temp[$j]];
                }
            }

            $words[$i] = [];
            for ($j = 0; $j < 4; $j++) {
                $words[$i][$j] = $words[$i - 8][$j] ^ $temp[$j];
            }
        }

        $roundKeys = [];
        for ($round = 0; $round <= 14; $round++) {
            $roundKey = [];
            for ($word = 0; $word < 4; $word++) {
                $roundKey = array_merge($roundKey, $words[$round * 4 + $word]);
            }
            $roundKeys[$round] = $roundKey;
        }

        $cache[$cacheKey] = $roundKeys;
        return $roundKeys;
    }
}

if (!function_exists('app_aes_sbox')) {
    function app_aes_sbox()
    {
        static $sbox = [
            0x63, 0x7c, 0x77, 0x7b, 0xf2, 0x6b, 0x6f, 0xc5, 0x30, 0x01, 0x67, 0x2b, 0xfe, 0xd7, 0xab, 0x76,
            0xca, 0x82, 0xc9, 0x7d, 0xfa, 0x59, 0x47, 0xf0, 0xad, 0xd4, 0xa2, 0xaf, 0x9c, 0xa4, 0x72, 0xc0,
            0xb7, 0xfd, 0x93, 0x26, 0x36, 0x3f, 0xf7, 0xcc, 0x34, 0xa5, 0xe5, 0xf1, 0x71, 0xd8, 0x31, 0x15,
            0x04, 0xc7, 0x23, 0xc3, 0x18, 0x96, 0x05, 0x9a, 0x07, 0x12, 0x80, 0xe2, 0xeb, 0x27, 0xb2, 0x75,
            0x09, 0x83, 0x2c, 0x1a, 0x1b, 0x6e, 0x5a, 0xa0, 0x52, 0x3b, 0xd6, 0xb3, 0x29, 0xe3, 0x2f, 0x84,
            0x53, 0xd1, 0x00, 0xed, 0x20, 0xfc, 0xb1, 0x5b, 0x6a, 0xcb, 0xbe, 0x39, 0x4a, 0x4c, 0x58, 0xcf,
            0xd0, 0xef, 0xaa, 0xfb, 0x43, 0x4d, 0x33, 0x85, 0x45, 0xf9, 0x02, 0x7f, 0x50, 0x3c, 0x9f, 0xa8,
            0x51, 0xa3, 0x40, 0x8f, 0x92, 0x9d, 0x38, 0xf5, 0xbc, 0xb6, 0xda, 0x21, 0x10, 0xff, 0xf3, 0xd2,
            0xcd, 0x0c, 0x13, 0xec, 0x5f, 0x97, 0x44, 0x17, 0xc4, 0xa7, 0x7e, 0x3d, 0x64, 0x5d, 0x19, 0x73,
            0x60, 0x81, 0x4f, 0xdc, 0x22, 0x2a, 0x90, 0x88, 0x46, 0xee, 0xb8, 0x14, 0xde, 0x5e, 0x0b, 0xdb,
            0xe0, 0x32, 0x3a, 0x0a, 0x49, 0x06, 0x24, 0x5c, 0xc2, 0xd3, 0xac, 0x62, 0x91, 0x95, 0xe4, 0x79,
            0xe7, 0xc8, 0x37, 0x6d, 0x8d, 0xd5, 0x4e, 0xa9, 0x6c, 0x56, 0xf4, 0xea, 0x65, 0x7a, 0xae, 0x08,
            0xba, 0x78, 0x25, 0x2e, 0x1c, 0xa6, 0xb4, 0xc6, 0xe8, 0xdd, 0x74, 0x1f, 0x4b, 0xbd, 0x8b, 0x8a,
            0x70, 0x3e, 0xb5, 0x66, 0x48, 0x03, 0xf6, 0x0e, 0x61, 0x35, 0x57, 0xb9, 0x86, 0xc1, 0x1d, 0x9e,
            0xe1, 0xf8, 0x98, 0x11, 0x69, 0xd9, 0x8e, 0x94, 0x9b, 0x1e, 0x87, 0xe9, 0xce, 0x55, 0x28, 0xdf,
            0x8c, 0xa1, 0x89, 0x0d, 0xbf, 0xe6, 0x42, 0x68, 0x41, 0x99, 0x2d, 0x0f, 0xb0, 0x54, 0xbb, 0x16,
        ];

        return $sbox;
    }
}

if (!function_exists('app_aes_add_round_key')) {
    function app_aes_add_round_key(array &$state, array $roundKey)
    {
        for ($i = 0; $i < 16; $i++) {
            $state[$i] ^= $roundKey[$i];
        }
    }
}

if (!function_exists('app_aes_sub_bytes')) {
    function app_aes_sub_bytes(array &$state)
    {
        $sbox = app_aes_sbox();
        for ($i = 0; $i < 16; $i++) {
            $state[$i] = $sbox[$state[$i]];
        }
    }
}

if (!function_exists('app_aes_shift_rows')) {
    function app_aes_shift_rows(array &$state)
    {
        $copy = $state;
        for ($row = 1; $row < 4; $row++) {
            for ($col = 0; $col < 4; $col++) {
                $state[$row + 4 * $col] = $copy[$row + 4 * (($col + $row) % 4)];
            }
        }
    }
}

if (!function_exists('app_aes_mix_columns')) {
    function app_aes_mix_columns(array &$state)
    {
        for ($col = 0; $col < 4; $col++) {
            $i = 4 * $col;
            $a0 = $state[$i];
            $a1 = $state[$i + 1];
            $a2 = $state[$i + 2];
            $a3 = $state[$i + 3];

            $state[$i] = (app_aes_gmul2($a0) ^ app_aes_gmul3($a1) ^ $a2 ^ $a3) & 0xff;
            $state[$i + 1] = ($a0 ^ app_aes_gmul2($a1) ^ app_aes_gmul3($a2) ^ $a3) & 0xff;
            $state[$i + 2] = ($a0 ^ $a1 ^ app_aes_gmul2($a2) ^ app_aes_gmul3($a3)) & 0xff;
            $state[$i + 3] = (app_aes_gmul3($a0) ^ $a1 ^ $a2 ^ app_aes_gmul2($a3)) & 0xff;
        }
    }
}

if (!function_exists('app_aes_gmul2')) {
    function app_aes_gmul2($value)
    {
        $value <<= 1;
        if ($value & 0x100) {
            $value ^= 0x11b;
        }

        return $value & 0xff;
    }
}

if (!function_exists('app_aes_gmul3')) {
    function app_aes_gmul3($value)
    {
        return app_aes_gmul2($value) ^ $value;
    }
}

if (!function_exists('app_string_to_bytes')) {
    function app_string_to_bytes($value)
    {
        $bytes = [];
        $length = strlen($value);
        for ($i = 0; $i < $length; $i++) {
            $bytes[] = ord($value[$i]);
        }

        return $bytes;
    }
}

if (!function_exists('app_bytes_to_string')) {
    function app_bytes_to_string(array $bytes)
    {
        $value = '';
        foreach ($bytes as $byte) {
            $value .= chr($byte & 0xff);
        }

        return $value;
    }
}
