<?php

namespace helvete\Tools;

class Flattener {

    const PRG_CHAR = '█';
    const PRG_STEP = 1.25;
    const DUP_MODE = 'antidupla';

    protected $src;
    protected $tar;
    protected $tot;
    protected $cur = 0;
    protected $prg = 0;
    protected $reg = [];

    public function __construct($srcDir, $progress = true, $dup = false) {
        $this->src = $srcDir;
        $this->tar = $this->asmblTarDir();
        if ($progress) {
            exec("/usr/bin/find {$this->src} -type f", $lines);
            $this->tot = count($lines);
        }
        $this->reg[self::DUP_MODE] = !(bool)$dup;
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
            $wrtOl = "{$root}/{$prev}.{$ext}";
            $wrtNe = "{$new}.{$ext}";
            if ($this->reg[self::DUP_MODE]) {
                $hash = md5(file_get_contents($wrtOl));
                if ($key = array_search($hash, $this->reg, true)) {
                    $this->logHash($wrtOl, $hash);
                    $this->prgrs();
                    continue;
                }
                $this->logHash($wrtOl, $hash);
            }

            $res = copy($wrtOl, $wrtNe);
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

    protected function logHash($fname, $hash) {
        $this->reg[$fname] = $hash;
    }

    public function getTot() {
        return $this->tot;
    }

    public function getDupLst() {
        if (!$this->reg[self::DUP_MODE]) {
            return [];
        }
        unset($this->reg[self::DUP_MODE]);
        $occ = array_count_values($this->reg);
        $dup = array_filter($occ, function($val) {
            return $val > 1;
        });
        $dupall = array_intersect($this->reg, array_keys($dup));
        return array_keys($dupall);
    }
}
