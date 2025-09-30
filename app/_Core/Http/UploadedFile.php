<?php
// app/_Core/Http/UploadedFile.php
namespace Core\Http;

use RuntimeException;

class UploadedFile
{
    private $name;
    private $type;
    private $tmpName;
    private $error;
    private $size;

    public function __construct(array $file)
    {
        $this->name     = $file['name'];
        $this->type     = $file['type'];
        $this->tmpName  = $file['tmp_name'];
        $this->error    = $file['error'];
        $this->size     = $file['size'];
    }
    
    public function moveTo(string $targetPath): bool
    {
        if (!$this->isValid()) {
            throw new RuntimeException('Cannot move file due to upload error.');
        }
        return move_uploaded_file($this->tmpName, $targetPath);
    }

    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    public function getClientOriginalName(): string
    {
        return $this->name;
    }

    public function getClientMimeType(): string
    {
        return $this->type;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getMimeType(): string
    {
        return $this->getClientMimeType();
    }

    public function getServerMimeType(): string
    {
        if (!$this->isValid() || !file_exists($this->tmpName)) {
            return '';
        }
        $finfo      = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType   = finfo_file($finfo, $this->tmpName);
        finfo_close($finfo);
        return $mimeType ?: '';
    }

    public function getTmpName(): string
    {
        return $this->tmpName;
    }
}