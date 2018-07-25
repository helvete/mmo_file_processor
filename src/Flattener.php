<?php

namespace helvete\Tools;

class Flattener {

    const PRG_CHAR = '█';
    const PRG_STEP = 1.25;

    protected $src;
    protected $tar;
    protected $tot;
    protected $cur = 0;
    protected $prg = 0;

    public function __construct($srcDir, $progress = true) {
        $this->src = $srcDir;
        $this->tar = $this->asmblTarDir();
        if ($progress) {
            exec("/usr/bin/find {$this->src} -type f", $lines);
            $this->tot = count($lines);
        }
    }

    protected function asmblTarDir() {
        $tar = "{$this->src}_DONE_" . time();
        if (!mkdir($tar)) {
            throw new \Exception("Cannot create output directory", 4);
        }

        return $tar;
    }

    public function transmogrify($root = null, $name = '') {
        $root = $root ?: $this->src;
        foreach (new \DirectoryIterator($root) as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }
            if ($fileInfo->isDir()) {
                $dirName = $fileInfo->getFilename();
                $this->transmogrify(
                    "{$root}/{$dirName}",
                    $name ? "{$name}_{$dirName}" : $dirName
                );
                continue;
            }
            $ext = $fileInfo->getExtension();
            $prev = $fileInfo->getBaseName(".{$ext}");
            $new = "{$this->tar}/" . self::name($name) . "_" . self::name($prev);

            if (file_exists("{$new}.{$ext}")) {
                $new .= '-qqq-' . md5(time());
            }

            $res = copy("{$root}/{$prev}.{$ext}", "{$new}.{$ext}");
            if (!$res) {
                throw new \Exception("Error: Cannot copy file {$new}", 5);
            }
            $this->prgrs();
        }
    }

    static public function name($source) {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $source);
    }

    protected function prgrs() {
        if (is_null($this->tot)) {
            return;
        }
        if (++$this->cur / $this->tot * 100 > $this->prg) {
            echo self::PRG_CHAR;
            $this->prg += self::PRG_STEP;
        }
    }

    public function getTot() {
        return $this->tot;
    }
}
