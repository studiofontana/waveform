<?php namespace Hudebnibanka\Waveform;

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
        $wavFile = $this->createOriginWavFile($mp3File);
        $stream  = fopen($wavFile, 'r');
        $heading = $this->getWavHeading($stream);

        $bitRate   = $this->getBitRate($heading[10]);
        $ratio     = $this->isStereo($heading[6]) ? 40 : 80;
        $dataSize  = floor((filesize($wavFile) - 44) / ($ratio + $bitRate) + 1);
        $dataPoint = 0;
        $detail    = ceil($dataSize / 3000);

        $result = [];
        while (!feof($stream) && $dataPoint < $dataSize) {
            if ($this->detailIsLacking($dataPoint++, $detail)) {
                fseek($stream, $ratio + $bitRate, SEEK_CUR);
                continue;
            }

            $bytes = $this->numberOfBytesDependingOnBitRate($bitRate, $stream);
            switch ($bitRate) {
                case self::BIT_RATE_8:
                    $result[] = $this->findValues($bytes[0], $bytes[1]);
                    break;
                case self::BIT_RATE_16:
                    if (ord($bytes[1]) & 128) {
                        $temp = 0;
                    } else {
                        $temp = 128;
                    }
                    $temp     = chr((ord($bytes[1]) & 127) + $temp);
                    $result[] = floor($this->findValues($bytes[0], $temp) / 256);
                    break;
            }

            $this->skipBytes($stream, $ratio);
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

    private function findValues($byte1, $byte2)
    {
        $byte1 = hexdec(bin2hex($byte1));
        $byte2 = hexdec(bin2hex($byte2));
        return ($byte1 + ($byte2 * 256));
    }

    /**
     * @param int $dataPoint
     */
    protected function detailIsLacking(int $dataPoint, $detail): bool
    {
        return $dataPoint % $detail != 0;
    }

    /**
     * @param resource $wavStream
     */
    protected function numberOfBytesDependingOnBitRate(int $bitRate, $wavStream): array
    {
        $bytes = [];
        for ($i = 0; $i < $bitRate; $i++) {
            $bytes[$i] = fgetc($wavStream);
        }
        return $bytes;
    }

    protected function isStereo($heading): bool
    {
        $channel = hexdec(substr($heading, 0, 2));
        return $channel == 2;
    }

    protected function createOriginWavFile(string $mp3File): string
    {
        $tempName = random_int(10000, 99999);
        $tempMp3  = $this->tempPath . "/$tempName.mp3";
        $tempWav  = $this->tempPath . "/$tempName.wav";
        $command  = "lame $mp3File -m m -S -f -b 16 --resample 8 $tempMp3 && lame -S --decode $tempMp3 $tempWav";
        shell_exec($command);
        unlink($tempMp3);
        return $tempWav;
    }

    /**
     * for memory optimization
     * @param resource $wavStream
     */
    protected function skipBytes($wavStream, int $ratio): void
    {
        fseek($wavStream, $ratio, SEEK_CUR);
    }

}