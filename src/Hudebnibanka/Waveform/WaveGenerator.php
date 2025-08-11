<?php

namespace Hudebnibanka\Waveform;

class WaveGenerator
{
    const BIT_RATE_8 = 1;
    const BIT_RATE_16 = 2;

    private $tempPath;

    public function __construct($tempPath)
    {
        $this->tempPath = $tempPath;
    }

    /**
     * Converts mp3 file to wav using lame
     * @return float[] wave values
     */
    public function generateWaves(string $mp3File): array
    {
        if (!$this->isMp3($mp3File)) {
            return [0];
        }

        $wavFile = $this->createOriginWavFile($mp3File);
        $stream  = fopen($wavFile, 'rb');

        $header = $this->getWavHeading($stream);
        $bitRate = $this->getBitRate($header[10]); // 1 or 2
        $isStereo = $this->isStereo($header[6]);   // true or false
        $channels = $isStereo ? 2 : 1;
        $bytesPerSample = $bitRate;
        $blockAlign = $channels * $bytesPerSample;

        $dataSize = floor((filesize($wavFile) - 44) / $blockAlign);
        $detail = ceil($dataSize / 3000);

        $result = [];

        for ($i = 0; $i < $dataSize; $i++) {
            if ($this->detailIsLacking($i, $detail)) {
                fseek($stream, $blockAlign, SEEK_CUR);
                continue;
            }

            $frame = fread($stream, $blockAlign);
            if (strlen($frame) < $blockAlign) {
                break; // Prevent underflow
            }

            $samples = [];

            for ($ch = 0; $ch < $channels; $ch++) {
                $offset = $ch * $bytesPerSample;
                $bytes = substr($frame, $offset, $bytesPerSample);

                if ($bitRate === self::BIT_RATE_8) {
                    $samples[] = ord($bytes);
                } elseif ($bitRate === self::BIT_RATE_16) {
                    $samples[] = unpack('s', $bytes)[1];
                }
            }

            // Mix stereo to mono if needed
            $avg = array_sum($samples) / count($samples);

            // Normalize 16-bit to 0–255
            if ($bitRate === self::BIT_RATE_16) {
                $normalized = floor(($avg + 32768) / 256);
            } else {
                $normalized = $avg; // Already 0–255
            }

            $result[] = max(0, min(255, (int)$normalized));
        }

        fclose($stream);
        unlink($wavFile);
        return $result;
    }

    /**
     * @param resource $wavStream
     */
    protected function getWavHeading($wavStream): array
    {
        $heading[] = fread($wavStream, 4);
        $heading[] = bin2hex(fread($wavStream, 4));
        $heading[] = fread($wavStream, 4);
        $heading[] = fread($wavStream, 4);
        $heading[] = bin2hex(fread($wavStream, 4));
        $heading[] = bin2hex(fread($wavStream, 2));
        $heading[] = bin2hex(fread($wavStream, 2));
        $heading[] = bin2hex(fread($wavStream, 4));
        $heading[] = bin2hex(fread($wavStream, 4));
        $heading[] = bin2hex(fread($wavStream, 2));
        $heading[] = bin2hex(fread($wavStream, 2));
        $heading[] = fread($wavStream, 4);
        $heading[] = bin2hex(fread($wavStream, 4));
        return $heading;
    }

    private function getBitRate(string $bitRateHeading): int
    {
        $peek = hexdec(substr($bitRateHeading, 0, 2));
        return $peek / 8;
    }

    /**
     * @param int $dataPoint
     */
    protected function detailIsLacking(int $dataPoint, $detail): bool
    {
        return $dataPoint % $detail != 0;
    }

    protected function isStereo($heading): bool
    {
        if (strlen($heading) < 2) return false;
        return hexdec(substr($heading, 0, 2)) === 2;
    }

    protected function createOriginWavFile(string $mp3File): string
    {
        $tempName = random_int(10000, 99999);
        $tempWav  = $this->tempPath . "/$tempName.wav";

        $command = "ffmpeg -y -i $mp3File -vn -map a -ac 1 -ar 44100 -acodec pcm_s16le $tempWav";
        shell_exec($command);
        return $tempWav;
    }

    protected function isMp3(string $file): bool
    {
        $cmd = 'ffprobe -v error -select_streams a:0 -show_entries stream=codec_name '
            . '-of default=nk=1:nw=1 ' . escapeshellarg($file) . ' 2>&1';
        exec($cmd, $out, $code);
        return $code === 0 && isset($out[0]) && trim($out[0]) === 'mp3';
    }
}