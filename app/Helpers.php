<?php
if ( ! function_exists('asset_versioned')) {
    function asset_versioned($file)
    {
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $file)) {

            $mtime = @filemtime($_SERVER['DOCUMENT_ROOT'] . $file);
            if ($mtime !== false) {
                return asset(
                    sprintf(
                        '/v%s%s',
                        $mtime,
                        $file
                    )
                );

            }
        }

        return $file;
    }

}
