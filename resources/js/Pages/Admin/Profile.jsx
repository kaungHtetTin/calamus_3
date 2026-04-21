import React from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import ImageCropper from '../../Components/Admin/ImageCropper';
import {
  Box,
  Typography,
  Grid,
  Paper,
  Stack,
  TextField,
  Button,
  Avatar,
  Divider,
  Alert,
  Snackbar,
  IconButton,
} from '@mui/material';
import {
  Person as PersonIcon,
  Lock as LockIcon,
  Save as SaveIcon,
  PhotoCamera as PhotoIcon,
} from '@mui/icons-material';

export default function Profile({ admin }) {
  const { flash, admin_app_url } = usePage().props;
  const [openSnackbar, setOpenSnackbar] = React.useState(false);
  const [cropperOpen, setCropperOpen] = React.useState(false);
  const [tempImage, setTempImage] = React.useState(null);
  const fileInputRef = React.useRef(null);

  // Profile Info Form
  const { data, setData, post, processing, errors, recentlySuccessful } = useForm({
    _method: 'PATCH', // To handle file upload with PATCH
    name: admin.name || '',
    image: null,
    image_preview: admin.image_url || '',
  });

  const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = () => {
        setTempImage(reader.result);
        setCropperOpen(true);
      };
      reader.readAsDataURL(file);
    }
  };

  const handleCropComplete = (blob) => {
    const reader = new FileReader();
    reader.readAsDataURL(blob);
    reader.onloadend = () => {
      const base64data = reader.result;
      setData((prev) => ({
        ...prev,
        image: base64data,
        image_preview: base64data,
      }));
    };
    setCropperOpen(false);
  };

  // Password Form
  const { 
    data: passwordData, 
    setData: setPasswordData, 
    put: updatePassword, 
    processing: passwordProcessing, 
    errors: passwordErrors,
    reset: resetPassword,
    recentlySuccessful: passwordSuccessful
  } = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
  });

  const handleProfileUpdate = (e) => {
    e.preventDefault();
    post(`${admin_app_url}/profile`, {
      onSuccess: () => setOpenSnackbar(true),
    });
  };

  const handlePasswordUpdate = (e) => {
    e.preventDefault();
    updatePassword(`${admin_app_url}/profile/password`, {
      onSuccess: () => {
        resetPassword();
        setOpenSnackbar(true);
      },
    });
  };

  return (
    <Box>
      <Head title="Admin Profile" />

      <Stack spacing={3}>
        <Box>
          <Typography variant="h5" sx={{ fontWeight: 700 }}>
            Account Settings
          </Typography>
          <Typography variant="body2" color="text.secondary">
            Manage your personal information and security settings.
          </Typography>
        </Box>

        <Grid container spacing={3}>
          {/* Profile Information */}
          <Grid item xs={12} md={7}>
            <Paper variant="outlined" sx={{ p: 3, borderRadius: 2 }}>
              <Stack spacing={3}>
                <Stack direction="row" spacing={2} alignItems="center">
                  <Box sx={{ position: 'relative' }}>
                    <Avatar 
                      src={data.image_preview} 
                      sx={{ width: 80, height: 80, bgcolor: 'primary.light', color: 'primary.main' }}
                    >
                      <PersonIcon fontSize="large" />
                    </Avatar>
                    <IconButton
                      size="small"
                      onClick={() => fileInputRef.current.click()}
                      sx={{
                        position: 'absolute',
                        bottom: 0,
                        right: 0,
                        bgcolor: 'primary.main',
                        color: 'white',
                        '&:hover': { bgcolor: 'primary.dark' },
                        boxShadow: 2,
                      }}
                    >
                      <PhotoIcon sx={{ fontSize: 16 }} />
                    </IconButton>
                    <input
                      type="file"
                      ref={fileInputRef}
                      onChange={handleFileChange}
                      accept="image/*"
                      style={{ display: 'none' }}
                    />
                  </Box>
                  <Box>
                    <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
                      Profile Details
                    </Typography>
                    <Typography variant="body2" color="text.secondary">
                      Update your name and profile picture.
                    </Typography>
                  </Box>
                </Stack>

                <Divider />

                <Box component="form" onSubmit={handleProfileUpdate}>
                  <Stack spacing={2.5}>
                    <TextField
                      fullWidth
                      label="Email Address"
                      value={admin.email}
                      disabled
                      helperText="Email address cannot be changed."
                      size="small"
                    />
                    <TextField
                      fullWidth
                      label="Full Name"
                      value={data.name}
                      onChange={(e) => setData('name', e.target.value)}
                      error={!!errors.name}
                      helperText={errors.name}
                      required
                      size="small"
                    />

                    <Box sx={{ display: 'flex', justifyContent: 'flex-end', pt: 1 }}>
                      <Button
                        type="submit"
                        variant="contained"
                        disabled={processing}
                        startIcon={<SaveIcon />}
                        sx={{ fontWeight: 600, px: 3 }}
                      >
                        {processing ? 'Saving...' : 'Save Changes'}
                      </Button>
                    </Box>
                  </Stack>
                </Box>
              </Stack>
            </Paper>
          </Grid>

          {/* Security / Password */}
          <Grid item xs={12} md={5}>
            <Paper variant="outlined" sx={{ p: 3, borderRadius: 2 }}>
              <Stack spacing={3}>
                <Stack direction="row" spacing={2} alignItems="center">
                  <Box sx={{ 
                    width: 40, 
                    height: 40, 
                    borderRadius: 1, 
                    bgcolor: 'error.lighter', 
                    color: 'error.main',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center'
                  }}>
                    <LockIcon fontSize="small" />
                  </Box>
                  <Box>
                    <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
                      Security
                    </Typography>
                    <Typography variant="body2" color="text.secondary">
                      Update your account password.
                    </Typography>
                  </Box>
                </Stack>

                <Divider />

                <Box component="form" onSubmit={handlePasswordUpdate}>
                  <Stack spacing={2}>
                    <TextField
                      fullWidth
                      type="password"
                      label="Current Password"
                      value={passwordData.current_password}
                      onChange={(e) => setPasswordData('current_password', e.target.value)}
                      error={!!passwordErrors.current_password}
                      helperText={passwordErrors.current_password}
                      required
                      size="small"
                    />
                    <TextField
                      fullWidth
                      type="password"
                      label="New Password"
                      value={passwordData.password}
                      onChange={(e) => setPasswordData('password', e.target.value)}
                      error={!!passwordErrors.password}
                      helperText={passwordErrors.password}
                      required
                      size="small"
                    />
                    <TextField
                      fullWidth
                      type="password"
                      label="Confirm New Password"
                      value={passwordData.password_confirmation}
                      onChange={(e) => setPasswordData('password_confirmation', e.target.value)}
                      error={!!passwordErrors.password_confirmation}
                      helperText={passwordErrors.password_confirmation}
                      required
                      size="small"
                    />

                    <Box sx={{ pt: 1 }}>
                      <Button
                        fullWidth
                        type="submit"
                        variant="outlined"
                        color="primary"
                        disabled={passwordProcessing}
                        sx={{ fontWeight: 600 }}
                      >
                        {passwordProcessing ? 'Updating...' : 'Update Password'}
                      </Button>
                    </Box>
                  </Stack>
                </Box>
              </Stack>
            </Paper>
          </Grid>
        </Grid>
      </Stack>

      <Snackbar
        open={openSnackbar || recentlySuccessful || passwordSuccessful}
        autoHideDuration={4000}
        onClose={() => setOpenSnackbar(false)}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
      >
        <Alert severity="success" sx={{ width: '100%' }}>
          Profile updated successfully!
        </Alert>
      </Snackbar>

      {/* Cropper Modal */}
      <ImageCropper
        open={cropperOpen}
        image={tempImage}
        onCropComplete={handleCropComplete}
        onCancel={() => setCropperOpen(false)}
      />
    </Box>
  );
}

Profile.layout = (page) => <AdminLayout children={page} />;
