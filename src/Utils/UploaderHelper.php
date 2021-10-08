<?php


namespace App\Utils;


use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploaderHelper
{

    public function uploadFile(UploadedFile $uploadedFile, string $destination): string
    {
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $fileName = $originalFilename . '-' . uniqid() .'.'. $uploadedFile->guessExtension();
        $uploadedFile->move($destination, $fileName);
        return $fileName;
    }

    public function deleteUploadedFile(string $filePath){
        unlink($filePath);
    }

}