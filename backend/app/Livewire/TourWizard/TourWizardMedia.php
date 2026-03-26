<?php

namespace App\Livewire\TourWizard;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class TourWizardMedia extends Component
{
    use WithFileUploads;

    public $tourId;
    public $images = [];
    public $tempImages = [];
    public $uploadedImages = [];

    public $maxImages = 20;
    public $maxImageSize = 5242880;
    public $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];

    public function mount($tempImages = [])
    {
        $this->tempImages = $tempImages;
    }

    public function updatedImages()
    {
        $this->validate([
            'images.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        foreach ($this->images as $image) {
            if (count($this->tempImages) >= $this->maxImages) {
                session()->flash('error', 'Solo puedes subir un máximo de 20 imágenes.');
                $this->images = [];
                break;
            }

            $tempPath = $image->store('temp/tours', 'public');

            $imageData = [
                'filename' => $image->getClientOriginalName(),
                'path' => $tempPath,
                'url' => Storage::url($tempPath),
                'size' => $image->getSize(),
                'mime' => $image->getMimeType(),
                'is_temp' => true
            ];

            $this->tempImages[] = $imageData;

            // Disparar evento para abrir el cropper automáticamente
            $this->dispatch('imageUploaded', [
                'index' => count($this->tempImages) - 1,
                'imageUrl' => Storage::url($tempPath)
            ]);
        }

        $this->images = [];
    }

    public function updateCroppedImage($index, $croppedImageData)
    {
        if (!isset($this->tempImages[$index])) {
            return;
        }

        // Eliminar imagen anterior si es temporal
        $oldImage = $this->tempImages[$index];
        if (isset($oldImage['is_temp']) && $oldImage['is_temp'] && Storage::disk('public')->exists($oldImage['path'])) {
            Storage::disk('public')->delete($oldImage['path']);
        }

        // Decodificar base64 y guardar la nueva imagen recortada
        $imageData = substr($croppedImageData, strpos($croppedImageData, ',') + 1);
        $imageData = base64_decode($imageData);

        // Generar nombre único para la imagen recortada
        $filename = 'cropped_' . time() . '_' . uniqid() . '.jpg';
        $path = 'temp/tours/' . $filename;

        // Guardar la imagen recortada
        Storage::disk('public')->put($path, $imageData);

        // Actualizar la información de la imagen
        $this->tempImages[$index] = [
            'filename' => $this->tempImages[$index]['filename'],
            'path' => $path,
            'url' => Storage::url($path),
            'size' => strlen($imageData),
            'mime' => 'image/jpeg',
            'is_temp' => true,
            'is_cropped' => true
        ];

        session()->flash('success', 'Imagen editada correctamente');
    }

    public function removeTempImage($index)
    {
        if (isset($this->tempImages[$index])) {
            $image = $this->tempImages[$index];
            
            if (isset($image['is_temp']) && $image['is_temp'] && Storage::disk('public')->exists($image['path'])) {
                Storage::disk('public')->delete($image['path']);
            }
            
            unset($this->tempImages[$index]);
            $this->tempImages = array_values($this->tempImages);
        }
    }

    public function setPrimaryImage($index)
    {
        if (count($this->tempImages) > 1 && $index > 0) {
            $primaryImage = $this->tempImages[$index];
            unset($this->tempImages[$index]);
            array_unshift($this->tempImages, $primaryImage);
            $this->tempImages = array_values($this->tempImages);
        }
    }

    public function moveImageUp($index)
    {
        if ($index > 0) {
            $temp = $this->tempImages[$index];
            $this->tempImages[$index] = $this->tempImages[$index - 1];
            $this->tempImages[$index - 1] = $temp;
        }
    }

    public function moveImageDown($index)
    {
        if ($index < count($this->tempImages) - 1) {
            $temp = $this->tempImages[$index];
            $this->tempImages[$index] = $this->tempImages[$index + 1];
            $this->tempImages[$index + 1] = $temp;
        }
    }

    public function getProgressPercentageProperty()
    {
        return min((count($this->tempImages) / $this->maxImages) * 100, 100);
    }

    public function render()
    {
        return view('livewire.tour-wizard.media');
    }
}