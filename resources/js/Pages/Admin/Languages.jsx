import React, { useMemo, useState } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import {
  Avatar,
  Alert,
  Box,
  Button,
  Card,
  CardContent,
  Chip,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  Divider,
  FormControlLabel,
  Grid,
  IconButton,
  InputAdornment,
  Menu,
  MenuItem,
  Paper,
  Snackbar,
  Stack,
  Switch,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  TextField,
  Typography,
  useTheme,
} from '@mui/material';
import {
  Add as AddIcon,
  Close as CloseIcon,
  MoreVert as MoreVertIcon,
  Language as LanguageIcon,
  Palette as PaletteIcon,
  Image as ImageIcon,
  Topic as TopicIcon,
  UploadFile as UploadFileIcon,
} from '@mui/icons-material';

const defaultFormData = {
  name: '',
  display_name: '',
  certificate_title: '',
  code: '',
  module_code: '',
  primary_color: '#3B82F6',
  secondary_color: '#10B981',
  image_file: null,
  seal_file: null,
  firebase_topic_user: '',
  firebase_topic_admin: '',
  sort_order: 0,
  is_active: true,
};

export default function Languages({ languages }) {
  const { admin_app_url, flash } = usePage().props;
  const theme = useTheme();
  const [openDialog, setOpenDialog] = useState(false);
  const [editingLanguage, setEditingLanguage] = useState(null);
  const [openSnackbar, setOpenSnackbar] = useState(false);
  const [imagePreview, setImagePreview] = useState('');
  const [sealPreview, setSealPreview] = useState('');
  const [imageFileName, setImageFileName] = useState('');
  const [sealFileName, setSealFileName] = useState('');
  const [languageMenuAnchorEl, setLanguageMenuAnchorEl] = useState(null);
  const [languageMenuRow, setLanguageMenuRow] = useState(null);

  const { data, setData, post, delete: destroy, processing, errors, reset, clearErrors } = useForm(defaultFormData);
  const appBaseUrl = admin_app_url.replace(/\/admin\/?$/, '');

  const activeCount = useMemo(
    () => (Array.isArray(languages) ? languages.filter((language) => Number(language.is_active) === 1).length : 0),
    [languages]
  );

  const buildImageUrl = (path) => {
    if (!path) {
      return '';
    }
    if (path.startsWith('http://') || path.startsWith('https://')) {
      return path;
    }
    const normalizedPath = path.startsWith('/') ? path : `/${path}`;
    return `${appBaseUrl}${normalizedPath}`;
  };

  const getFileNameFromPath = (path) => {
    if (!path) {
      return '';
    }
    const normalized = String(path).split('?')[0];
    return normalized.split('/').pop() || '';
  };

  const handleOpenDialog = (language = null) => {
    clearErrors();
    if (language) {
      setEditingLanguage(language);
      setData({
        name: language.name || '',
        display_name: language.display_name || '',
        certificate_title: language.certificate_title || '',
        code: language.code || '',
        module_code: language.module_code || '',
        primary_color: language.primary_color || '#3B82F6',
        secondary_color: language.secondary_color || '#10B981',
        image_file: null,
        seal_file: null,
        firebase_topic_user: language.firebase_topic_user || '',
        firebase_topic_admin: language.firebase_topic_admin || '',
        sort_order: Number(language.sort_order || 0),
        is_active: Number(language.is_active) === 1,
      });
      setImagePreview(buildImageUrl(language.image_path));
      setSealPreview(buildImageUrl(language.seal));
      setImageFileName(getFileNameFromPath(language.image_path));
      setSealFileName(getFileNameFromPath(language.seal));
    } else {
      setEditingLanguage(null);
      reset();
      setData(defaultFormData);
      setImagePreview('');
      setSealPreview('');
      setImageFileName('');
      setSealFileName('');
    }
    setOpenDialog(true);
  };

  const handleCloseDialog = () => {
    setOpenDialog(false);
    setEditingLanguage(null);
    reset();
    setData(defaultFormData);
    setImagePreview('');
    setSealPreview('');
    setImageFileName('');
    setSealFileName('');
  };

  const handleSubmit = (event) => {
    event.preventDefault();
    const normalizedPayload = {
      ...data,
      sort_order: Number(data.sort_order || 0),
      is_active: data.is_active ? 1 : 0,
    };

    if (editingLanguage) {
      post(`${admin_app_url}/languages/${editingLanguage.id}`, {
        forceFormData: true,
        data: {
          ...normalizedPayload,
          _method: 'patch',
        },
        onSuccess: () => {
          handleCloseDialog();
          setOpenSnackbar(true);
        },
      });
      return;
    }

    post(`${admin_app_url}/languages`, {
      forceFormData: true,
      data: normalizedPayload,
      onSuccess: () => {
        handleCloseDialog();
        setOpenSnackbar(true);
      },
    });
  };

  const handleDelete = (language) => {
    if (!confirm(`Delete language "${language.display_name || language.name}"?`)) {
      return;
    }
    destroy(`${admin_app_url}/languages/${language.id}`, {
      onSuccess: () => setOpenSnackbar(true),
    });
  };

  const openLanguageMenu = (event, language) => {
    setLanguageMenuAnchorEl(event.currentTarget);
    setLanguageMenuRow(language);
  };

  const closeLanguageMenu = () => {
    setLanguageMenuAnchorEl(null);
    setLanguageMenuRow(null);
  };

  const handleImageChange = (field, previewSetter, nameSetter) => (event) => {
    const file = event.target.files?.[0] || null;
    setData(field, file);
    previewSetter(file ? URL.createObjectURL(file) : '');
    nameSetter(file ? file.name : '');
  };

  const clearImageField = (field, previewSetter, nameSetter) => () => {
    setData(field, null);
    previewSetter('');
    nameSetter('');
  };

  return (
    <Box>
      <Head title="Language Management" />

      <Stack spacing={2.5}>
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              Language Management
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Create, update, and manage application languages.
            </Typography>
          </Box>
          <Button variant="contained" startIcon={<AddIcon />} onClick={() => handleOpenDialog()} sx={{ borderRadius: 2, fontWeight: 600 }}>
            Add Language
          </Button>
        </Box>

        <Grid container spacing={1.5}>
          <Grid item xs={12} sm={6} md={3}>
            <Card variant="outlined">
              <CardContent sx={{ p: 1.5, '&:last-child': { pb: 1.5 } }}>
                <Typography variant="body2" color="text.secondary">Total Languages</Typography>
                <Typography variant="h5" sx={{ fontWeight: 700, mt: 0.5 }}>{languages.length}</Typography>
              </CardContent>
            </Card>
          </Grid>
          <Grid item xs={12} sm={6} md={3}>
            <Card variant="outlined">
              <CardContent sx={{ p: 1.5, '&:last-child': { pb: 1.5 } }}>
                <Typography variant="body2" color="text.secondary">Active Languages</Typography>
                <Typography variant="h5" sx={{ fontWeight: 700, mt: 0.5 }}>{activeCount}</Typography>
              </CardContent>
            </Card>
          </Grid>
        </Grid>

        <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
          <Table size="small">
            <TableHead>
              <TableRow sx={{ bgcolor: theme.palette.mode === 'dark' ? 'rgba(255,255,255,0.03)' : 'grey.50' }}>
                <TableCell sx={{ fontWeight: 700 }}>Language</TableCell>
                <TableCell sx={{ fontWeight: 700 }}>Code</TableCell>
                <TableCell sx={{ fontWeight: 700 }}>Module</TableCell>
                <TableCell sx={{ fontWeight: 700 }}>Colors</TableCell>
                <TableCell sx={{ fontWeight: 700 }}>Sort</TableCell>
                <TableCell sx={{ fontWeight: 700 }}>Status</TableCell>
                <TableCell align="right" sx={{ fontWeight: 700 }}>Actions</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {languages.map((language) => (
                <TableRow key={language.id} hover>
                  <TableCell>
                    <Stack direction="row" spacing={1.25} alignItems="center">
                      <Avatar
                        src={buildImageUrl(language.image_path)}
                        sx={{ width: 28, height: 28, bgcolor: 'action.selected' }}
                      >
                        <LanguageIcon fontSize="small" color="action" />
                      </Avatar>
                      <Box>
                        <Typography variant="body2" sx={{ fontWeight: 700 }}>
                          {language.display_name || language.name}
                        </Typography>
                        <Typography variant="caption" color="text.secondary">
                          {language.name}
                          {language.firebase_topic_user ? ` • user: ${language.firebase_topic_user}` : ''}
                          {language.firebase_topic_admin ? ` • admin: ${language.firebase_topic_admin}` : ''}
                        </Typography>
                      </Box>
                    </Stack>
                  </TableCell>
                  <TableCell>{language.code || '-'}</TableCell>
                  <TableCell>{language.module_code || '-'}</TableCell>
                  <TableCell>
                    <Stack direction="row" spacing={0.75}>
                      <Chip
                        size="small"
                        label={language.primary_color || 'N/A'}
                        sx={{
                          bgcolor: language.primary_color || 'transparent',
                          color: theme.palette.getContrastText(language.primary_color || '#ffffff'),
                          border: '1px solid',
                          borderColor: 'divider',
                        }}
                      />
                      <Chip
                        size="small"
                        label={language.secondary_color || 'N/A'}
                        sx={{
                          bgcolor: language.secondary_color || 'transparent',
                          color: theme.palette.getContrastText(language.secondary_color || '#ffffff'),
                          border: '1px solid',
                          borderColor: 'divider',
                        }}
                      />
                    </Stack>
                  </TableCell>
                  <TableCell>{language.sort_order ?? 0}</TableCell>
                  <TableCell>
                    <Chip
                      size="small"
                      label={Number(language.is_active) === 1 ? 'Active' : 'Inactive'}
                      color={Number(language.is_active) === 1 ? 'success' : 'default'}
                      variant={Number(language.is_active) === 1 ? 'filled' : 'outlined'}
                    />
                  </TableCell>
                  <TableCell align="right">
                    <IconButton size="small" onClick={(e) => openLanguageMenu(e, language)} title="Actions">
                      <MoreVertIcon fontSize="small" />
                    </IconButton>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </TableContainer>
      </Stack>

      <Menu
        anchorEl={languageMenuAnchorEl}
        open={Boolean(languageMenuAnchorEl)}
        onClose={closeLanguageMenu}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
        transformOrigin={{ vertical: 'top', horizontal: 'right' }}
      >
        <MenuItem
          onClick={() => {
            handleOpenDialog(languageMenuRow);
            closeLanguageMenu();
          }}
        >
          Edit
        </MenuItem>
        <MenuItem
          sx={{ color: 'error.main' }}
          onClick={() => {
            handleDelete(languageMenuRow);
            closeLanguageMenu();
          }}
        >
          Delete
        </MenuItem>
      </Menu>

      <Dialog open={openDialog} onClose={handleCloseDialog} maxWidth="md" fullWidth>
        <DialogTitle>
          <Stack direction="row" spacing={1} alignItems="center" justifyContent="space-between">
            <Typography variant="inherit" component="div">
              {editingLanguage ? 'Edit Language' : 'Add Language'}
            </Typography>
            {editingLanguage?.id ? (
              <Chip size="small" variant="outlined" label={`ID: ${editingLanguage.id}`} />
            ) : null}
          </Stack>
        </DialogTitle>
        <Box component="form" onSubmit={handleSubmit}>
          <DialogContent dividers>
            <Stack spacing={3}>
              <Box>
                <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 0.75 }}>
                  Basic Information
                </Typography>
                <Divider sx={{ mb: 2 }} />
                <Box
                  sx={{
                    display: 'grid',
                    gridTemplateColumns: { xs: '1fr', md: 'repeat(2, minmax(0, 1fr))' },
                    gap: 2,
                  }}
                >
                  <TextField
                    fullWidth
                    label="Name"
                    value={data.name}
                    onChange={(event) => setData('name', event.target.value)}
                    error={Boolean(errors.name)}
                    helperText={errors.name}
                  />
                  <TextField
                    fullWidth
                    label="Display Name"
                    value={data.display_name}
                    onChange={(event) => setData('display_name', event.target.value)}
                    error={Boolean(errors.display_name)}
                    helperText={errors.display_name}
                  />
                  <TextField
                    fullWidth
                    label="Certificate Title"
                    value={data.certificate_title}
                    onChange={(event) => setData('certificate_title', event.target.value)}
                    error={Boolean(errors.certificate_title)}
                    helperText={errors.certificate_title}
                  />
                  <TextField
                    fullWidth
                    label="Code"
                    value={data.code}
                    onChange={(event) => setData('code', event.target.value)}
                    error={Boolean(errors.code)}
                    helperText={errors.code}
                  />
                  <TextField
                    fullWidth
                    label="Module Code"
                    value={data.module_code}
                    onChange={(event) => setData('module_code', event.target.value)}
                    error={Boolean(errors.module_code)}
                    helperText={errors.module_code}
                  />
                  <TextField
                    fullWidth
                    label="Firebase Topic (User)"
                    value={data.firebase_topic_user}
                    onChange={(event) => setData('firebase_topic_user', event.target.value)}
                    error={Boolean(errors.firebase_topic_user)}
                    helperText={errors.firebase_topic_user}
                    InputProps={{
                      startAdornment: (
                        <InputAdornment position="start">
                          <TopicIcon fontSize="small" color="action" />
                        </InputAdornment>
                      ),
                    }}
                  />
                  <TextField
                    fullWidth
                    label="Firebase Topic (Admin)"
                    value={data.firebase_topic_admin}
                    onChange={(event) => setData('firebase_topic_admin', event.target.value)}
                    error={Boolean(errors.firebase_topic_admin)}
                    helperText={errors.firebase_topic_admin}
                    InputProps={{
                      startAdornment: (
                        <InputAdornment position="start">
                          <TopicIcon fontSize="small" color="action" />
                        </InputAdornment>
                      ),
                    }}
                  />
                  <TextField
                    fullWidth
                    type="number"
                    label="Sort Order"
                    value={data.sort_order}
                    onChange={(event) => setData('sort_order', event.target.value)}
                    error={Boolean(errors.sort_order)}
                    helperText={errors.sort_order}
                    inputProps={{ min: 0 }}
                  />
                  <Box sx={{ display: 'flex', alignItems: 'center', minHeight: 56 }}>
                    <FormControlLabel
                      control={
                        <Switch
                          checked={Boolean(data.is_active)}
                          onChange={(event) => setData('is_active', event.target.checked)}
                        />
                      }
                      label="Active"
                    />
                  </Box>
                </Box>
              </Box>

              <Box>
                <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 0.75 }}>
                  Theme Colors
                </Typography>
                <Divider sx={{ mb: 2 }} />
                <Box
                  sx={{
                    display: 'grid',
                    gridTemplateColumns: { xs: '1fr', md: 'repeat(2, minmax(0, 1fr))' },
                    gap: 2,
                  }}
                >
                  <TextField
                    fullWidth
                    label="Primary Color"
                    value={data.primary_color}
                    onChange={(event) => setData('primary_color', event.target.value)}
                    error={Boolean(errors.primary_color)}
                    helperText={errors.primary_color}
                    InputProps={{
                      startAdornment: (
                        <InputAdornment position="start">
                          <Box component="input" type="color" value={data.primary_color || '#3B82F6'} onChange={(event) => setData('primary_color', event.target.value)} sx={{ width: 24, height: 24, p: 0, border: 0, bgcolor: 'transparent', cursor: 'pointer' }} />
                        </InputAdornment>
                      ),
                      endAdornment: (
                        <InputAdornment position="end">
                          <PaletteIcon fontSize="small" color="action" />
                        </InputAdornment>
                      ),
                    }}
                  />
                  <TextField
                    fullWidth
                    label="Secondary Color"
                    value={data.secondary_color}
                    onChange={(event) => setData('secondary_color', event.target.value)}
                    error={Boolean(errors.secondary_color)}
                    helperText={errors.secondary_color}
                    InputProps={{
                      startAdornment: (
                        <InputAdornment position="start">
                          <Box component="input" type="color" value={data.secondary_color || '#10B981'} onChange={(event) => setData('secondary_color', event.target.value)} sx={{ width: 24, height: 24, p: 0, border: 0, bgcolor: 'transparent', cursor: 'pointer' }} />
                        </InputAdornment>
                      ),
                      endAdornment: (
                        <InputAdornment position="end">
                          <PaletteIcon fontSize="small" color="action" />
                        </InputAdornment>
                      ),
                    }}
                  />
                </Box>
              </Box>

              <Box>
                <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 0.75 }}>
                  Images
                </Typography>
                <Divider sx={{ mb: 2 }} />
                <Box
                  sx={{
                    display: 'grid',
                    gridTemplateColumns: { xs: '1fr', md: 'repeat(2, minmax(0, 1fr))' },
                    gap: 2,
                  }}
                >
                  <Stack spacing={1.25}>
                    <Typography variant="body2" sx={{ fontWeight: 600 }}>
                      Image
                    </Typography>
                    <Stack direction="row" spacing={1} alignItems="center">
                      <Button
                        component="label"
                        variant="outlined"
                        startIcon={<UploadFileIcon />}
                        sx={{ minWidth: 132 }}
                      >
                        Choose File
                        <input
                          hidden
                          type="file"
                          accept="image/*"
                          onChange={handleImageChange('image_file', setImagePreview, setImageFileName)}
                        />
                      </Button>
                      <Chip
                        size="small"
                        variant="outlined"
                        icon={<ImageIcon />}
                        label={imageFileName || 'No file selected'}
                        sx={{ maxWidth: '100%' }}
                      />
                      {imageFileName && (
                        <IconButton size="small" onClick={clearImageField('image_file', setImagePreview, setImageFileName)}>
                          <CloseIcon fontSize="small" />
                        </IconButton>
                      )}
                    </Stack>
                    <Box sx={{ height: 148, border: '1px dashed', borderColor: errors.image_file ? 'error.main' : 'divider', borderRadius: 2, display: 'flex', alignItems: 'center', justifyContent: 'center', overflow: 'hidden', bgcolor: 'action.hover' }}>
                      {imagePreview ? (
                        <Box component="img" src={imagePreview} alt="Language preview" sx={{ maxHeight: '100%', maxWidth: '100%', objectFit: 'contain' }} />
                      ) : (
                        <Stack direction="row" spacing={1} alignItems="center">
                          <ImageIcon fontSize="small" color="action" />
                          <Typography variant="caption" color="text.secondary">No image selected</Typography>
                        </Stack>
                      )}
                    </Box>
                    <Typography variant="caption" color={errors.image_file ? 'error.main' : 'text.secondary'}>
                      {errors.image_file || 'Accepted formats: JPG, PNG, WEBP, GIF. Max 4MB.'}
                    </Typography>
                  </Stack>
                  <Stack spacing={1.25}>
                    <Typography variant="body2" sx={{ fontWeight: 600 }}>
                      Seal
                    </Typography>
                    <Stack direction="row" spacing={1} alignItems="center">
                      <Button
                        component="label"
                        variant="outlined"
                        startIcon={<UploadFileIcon />}
                        sx={{ minWidth: 132 }}
                      >
                        Choose File
                        <input
                          hidden
                          type="file"
                          accept="image/*"
                          onChange={handleImageChange('seal_file', setSealPreview, setSealFileName)}
                        />
                      </Button>
                      <Chip
                        size="small"
                        variant="outlined"
                        icon={<ImageIcon />}
                        label={sealFileName || 'No file selected'}
                        sx={{ maxWidth: '100%' }}
                      />
                      {sealFileName && (
                        <IconButton size="small" onClick={clearImageField('seal_file', setSealPreview, setSealFileName)}>
                          <CloseIcon fontSize="small" />
                        </IconButton>
                      )}
                    </Stack>
                    <Box sx={{ height: 148, border: '1px dashed', borderColor: errors.seal_file ? 'error.main' : 'divider', borderRadius: 2, display: 'flex', alignItems: 'center', justifyContent: 'center', overflow: 'hidden', bgcolor: 'action.hover' }}>
                      {sealPreview ? (
                        <Box component="img" src={sealPreview} alt="Seal preview" sx={{ maxHeight: '100%', maxWidth: '100%', objectFit: 'contain' }} />
                      ) : (
                        <Stack direction="row" spacing={1} alignItems="center">
                          <ImageIcon fontSize="small" color="action" />
                          <Typography variant="caption" color="text.secondary">No seal selected</Typography>
                        </Stack>
                      )}
                    </Box>
                    <Typography variant="caption" color={errors.seal_file ? 'error.main' : 'text.secondary'}>
                      {errors.seal_file || 'Accepted formats: JPG, PNG, WEBP, GIF. Max 4MB.'}
                    </Typography>
                  </Stack>
                </Box>
              </Box>
            </Stack>
          </DialogContent>
          <DialogActions>
            <Button onClick={handleCloseDialog} disabled={processing}>Cancel</Button>
            <Button type="submit" variant="contained" disabled={processing}>
              {editingLanguage ? 'Update' : 'Create'}
            </Button>
          </DialogActions>
        </Box>
      </Dialog>

      <Snackbar
        open={openSnackbar || Boolean(flash?.success)}
        autoHideDuration={3000}
        onClose={() => setOpenSnackbar(false)}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
      >
        <Alert severity="success" variant="filled" onClose={() => setOpenSnackbar(false)}>
          {flash?.success || 'Action completed successfully.'}
        </Alert>
      </Snackbar>
    </Box>
  );
}

Languages.layout = (page) => <AdminLayout children={page} />;
