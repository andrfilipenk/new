<?php
// app/Core/Http/UploadedFile.php
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
        $this->name = $file['name'];
        $this->type = $file['type'];
        $this->tmpName = $file['tmp_name'];
        $this->error = $file['error'];
        $this->size = $file['size'];
    }

    /**
     * Move the uploaded file to a new location.
     */
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
}