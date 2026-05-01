import React, { useState, useCallback } from 'react';
import Cropper from 'react-easy-crop';
import {
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Button,
  Slider,
  Typography,
  Box,
} from '@mui/material';

export default function ImageCropper({
  open,
  image,
  onCropComplete,
  onCancel,
  aspect = 1 / 1,
  title = 'Crop Image',
  outputWidth = null,
  outputHeight = null,
  cornerRadius = 15,
}) {
  const [crop, setCrop] = useState({ x: 0, y: 0 });
  const [zoom, setZoom] = useState(1);
  const [croppedAreaPixels, setCroppedAreaPixels] = useState(null);

  const onCropChange = (crop) => {
    setCrop(crop);
  };

  const onZoomChange = (zoom) => {
    setZoom(zoom);
  };

  const onCropCompleteInternal = useCallback((_croppedArea, croppedAreaPixels) => {
    setCroppedAreaPixels(croppedAreaPixels);
  }, []);

  const createImage = (url) =>
    new Promise((resolve, reject) => {
      const image = new Image();
      image.addEventListener('load', () => resolve(image));
      image.addEventListener('error', (error) => reject(error));
      image.setAttribute('crossOrigin', 'anonymous');
      image.src = url;
    });

  const getCroppedImg = async (imageSrc, pixelCrop) => {
    const image = await createImage(imageSrc);
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');

    if (!ctx) {
      return null;
    }

    const targetW = Number.isFinite(Number(outputWidth)) && Number(outputWidth) > 0 ? Math.round(Number(outputWidth)) : pixelCrop.width;
    const targetH = Number.isFinite(Number(outputHeight)) && Number(outputHeight) > 0 ? Math.round(Number(outputHeight)) : pixelCrop.height;

    canvas.width = targetW;
    canvas.height = targetH;

    ctx.drawImage(
      image,
      pixelCrop.x,
      pixelCrop.y,
      pixelCrop.width,
      pixelCrop.height,
      0,
      0,
      targetW,
      targetH
    );

    return new Promise((resolve) => {
      canvas.toBlob((blob) => {
        if (!blob) {
          return;
        }
        resolve(blob);
      }, 'image/jpeg');
    });
  };

  const handleSave = async () => {
    try {
      const croppedImageBlob = await getCroppedImg(image, croppedAreaPixels);
      onCropComplete(croppedImageBlob);
    } catch (e) {
      console.error(e);
    }
  };

  return (
    <Dialog open={open} onClose={onCancel} maxWidth="sm" fullWidth>
      <DialogTitle>{title}</DialogTitle>
      <DialogContent
        sx={{
          position: 'relative',
          height: 400,
          bgcolor: '#333',
          '& .reactEasyCrop_CropArea': {
            borderRadius: `${Number.isFinite(Number(cornerRadius)) ? Math.max(0, Number(cornerRadius)) : 0}px`,
          },
        }}
      >
        <Cropper
          image={image}
          crop={crop}
          zoom={zoom}
          aspect={aspect}
          onCropChange={onCropChange}
          onCropComplete={onCropCompleteInternal}
          onZoomChange={onZoomChange}
        />
      </DialogContent>
      <DialogActions sx={{ flexDirection: 'column', p: 2, alignItems: 'stretch', gap: 2 }}>
        <Box sx={{ px: 2 }}>
          <Typography variant="caption" color="text.secondary">Zoom</Typography>
          <Slider
            value={zoom}
            min={1}
            max={3}
            step={0.1}
            aria-labelledby="Zoom"
            onChange={(e, zoom) => setZoom(zoom)}
          />
        </Box>
        <Box sx={{ display: 'flex', justifyContent: 'flex-end', gap: 1 }}>
          <Button onClick={onCancel}>Cancel</Button>
          <Button variant="contained" onClick={handleSave}>Apply Crop</Button>
        </Box>
      </DialogActions>
    </Dialog>
  );
}
