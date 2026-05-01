import React from 'react';
import { Head, useForm, usePage, router } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import ImageCropper from '../../Components/Admin/ImageCropper';
import {
  Avatar,
  Box,
  Button,
  Chip,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  Divider,
  IconButton,
  ListItemIcon,
  ListItemText,
  Menu,
  MenuItem,
  Paper,
  Stack,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  TextField,
  Typography,
} from '@mui/material';
import {
  Add as AddIcon,
  Edit as EditIcon,
  Delete as DeleteIcon,
  MoreVert as MoreVertIcon,
  Language as LanguageIcon,
  Image as ImageIcon,
  OpenInNew as OpenInNewIcon,
} from '@mui/icons-material';

export default function Banners({ banners, languageOptions }) {
  const { admin_app_url } = usePage().props;
  const rows = Array.isArray(banners) ? banners : [];
  const languages = Array.isArray(languageOptions) ? languageOptions : [];
  const appBaseUrl = admin_app_url.replace(/\/admin\/?$/, '');

  const [openDialog, setOpenDialog] = React.useState(false);
  const [editing, setEditing] = React.useState(null);
  const [rowMenu, setRowMenu] = React.useState({ anchorEl: null, row: null });
  const rowMenuOpen = Boolean(rowMenu.anchorEl);
  const [imagePreview, setImagePreview] = React.useState('');
  const [imageCropper, setImageCropper] = React.useState({ open: false, image: '', fileType: 'image/jpeg', fileName: 'banner.jpg' });

  const buildImageUrl = (path) => {
    if (!path) return '';
    const raw = String(path);
    if (raw.startsWith('http://') || raw.startsWith('https://') || raw.startsWith('blob:') || raw.startsWith('data:')) return raw;
    const normalizedPath = String(path).startsWith('/') ? path : `/${path}`;
    return `${appBaseUrl}${normalizedPath}`;
  };

  const languageByCode = React.useMemo(() => {
    const map = new Map();
    languages.forEach((l) => {
      const code = String(l.code || '').toLowerCase().trim();
      if (!code) return;
      map.set(code, l);
    });
    return map;
  }, [languages]);

  const { data, setData, post, patch, processing, errors, reset, clearErrors } = useForm({
    title: '',
    major: '',
    image_url: '',
    link: '',
    image: null,
  });

  const handleOpenAdd = () => {
    setEditing(null);
    reset();
    clearErrors();
    setImagePreview('');
    setData({
      title: '',
      major: languages[0]?.code || '',
      image_url: '',
      link: '',
      image: null,
    });
    setOpenDialog(true);
  };

  const handleOpenEdit = (row) => {
    setEditing(row);
    clearErrors();
    setImagePreview(row.image_url || '');
    setData({
      title: row.title || '',
      major: row.major || '',
      image_url: row.image_url || '',
      link: row.link || '',
      image: null,
    });
    setOpenDialog(true);
  };

  const handleClose = () => {
    setOpenDialog(false);
    setEditing(null);
    reset();
    clearErrors();
    setImagePreview('');
    setImageCropper({ open: false, image: '', fileType: 'image/jpeg', fileName: 'banner.jpg' });
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (editing) {
      patch(`${admin_app_url}/banners/${editing.id}`, { preserveScroll: true, onSuccess: handleClose });
      return;
    }
    post(`${admin_app_url}/banners`, { preserveScroll: true, onSuccess: handleClose });
  };

  const handleDelete = (row) => {
    if (!confirm(`Delete banner \"${row.title || ''}\"?`)) return;
    router.delete(`${admin_app_url}/banners/${row.id}`, { preserveScroll: true });
  };

  const openRowMenu = (event, row) => setRowMenu({ anchorEl: event.currentTarget, row });
  const closeRowMenu = () => setRowMenu({ anchorEl: null, row: null });

  const selectedLang = languageByCode.get(String(data.major || '').toLowerCase().trim());
  const previewImageUrl = imagePreview ? buildImageUrl(imagePreview) : buildImageUrl(data.image_url);

  const handleSelectImage = () => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = (e) => {
      const file = e.target.files?.[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = () => {
        setImageCropper({
          open: true,
          image: String(reader.result || ''),
          fileType: file.type || 'image/jpeg',
          fileName: file.name || 'banner.jpg',
        });
      };
      reader.readAsDataURL(file);
    };
    input.click();
  };

  return (
    <Box>
      <Head title="Banners" />

      <Stack spacing={1.5}>
        <Stack direction="row" justifyContent="space-between" alignItems="center">
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              Banners
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Manage banners by language.
            </Typography>
          </Box>
          <Button variant="contained" size="small" startIcon={<AddIcon />} onClick={handleOpenAdd}>
            Add Banner
          </Button>
        </Stack>

        <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
          <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
            <Table size="small" sx={{ tableLayout: 'auto', '& .MuiTableCell-root': { px: 1, py: 0.75, verticalAlign: 'top' } }}>
              <TableHead>
                <TableRow>
                  <TableCell sx={{ fontWeight: 700, width: 220 }}>Major</TableCell>
                  <TableCell sx={{ fontWeight: 700, minWidth: 220 }}>Title</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 110 }}>Image</TableCell>
                  <TableCell sx={{ fontWeight: 700, minWidth: 340 }}>Link</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 90 }}>Actions</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {rows.map((row) => {
                  const lang = languageByCode.get(String(row.major || '').toLowerCase().trim());
                  const rowImageUrl = buildImageUrl(row.image_url);
                  return (
                    <TableRow key={`banner-${row.id}`} hover>
                      <TableCell>
                        {lang ? (
                          <Stack direction="row" spacing={1} alignItems="center">
                            <Avatar
                              src={buildImageUrl(lang.image_path)}
                              sx={{ width: 24, height: 24, bgcolor: lang.primary_color || 'action.selected' }}
                            >
                              <LanguageIcon fontSize="small" />
                            </Avatar>
                            <Box>
                              <Typography variant="body2" sx={{ fontWeight: 700, lineHeight: 1.15 }}>
                                {lang.name}
                              </Typography>
                              <Typography variant="caption" color="text.secondary" sx={{ lineHeight: 1.1 }}>
                                {row.major}
                              </Typography>
                            </Box>
                          </Stack>
                        ) : (
                          <Typography variant="body2">{row.major || '-'}</Typography>
                        )}
                      </TableCell>
                      <TableCell>
                        <Typography variant="body2" sx={{ fontWeight: 700 }}>
                          {row.title || '-'}
                        </Typography>
                      </TableCell>
                      <TableCell>
                        <Avatar
                          variant="rounded"
                          src={rowImageUrl}
                          sx={{ width: 64, height: 36, bgcolor: 'action.selected', borderRadius: 1 }}
                        >
                          <ImageIcon fontSize="small" />
                        </Avatar>
                      </TableCell>
                      <TableCell>
                        {row.link ? (
                          <Stack spacing={0.25}>
                            <Typography variant="body2" sx={{ wordBreak: 'break-all' }}>
                              {row.link}
                            </Typography>
                            <Stack direction="row" spacing={1} alignItems="center">
                              <Chip
                                size="small"
                                label="Open"
                                variant="outlined"
                                icon={<OpenInNewIcon />}
                                onClick={() => window.open(row.link, '_blank')}
                              />
                              <Chip
                                size="small"
                                label="Copy"
                                variant="outlined"
                                onClick={() => navigator.clipboard.writeText(String(row.link || ''))}
                              />
                            </Stack>
                          </Stack>
                        ) : (
                          <Typography variant="body2" color="text.secondary">
                            -
                          </Typography>
                        )}
                      </TableCell>
                      <TableCell>
                        <IconButton size="small" aria-label="actions" onClick={(e) => openRowMenu(e, row)}>
                          <MoreVertIcon fontSize="small" />
                        </IconButton>
                      </TableCell>
                    </TableRow>
                  );
                })}
                {rows.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={5}>
                      <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                        No banners found.
                      </Typography>
                    </TableCell>
                  </TableRow>
                ) : null}
              </TableBody>
            </Table>
          </TableContainer>
        </Paper>
      </Stack>

      <Dialog open={openDialog} onClose={handleClose} maxWidth="sm" fullWidth>
        <DialogTitle sx={{ fontWeight: 700 }}>
          <Stack direction="row" alignItems="center" justifyContent="space-between" spacing={2}>
            <Stack direction="row" spacing={1} alignItems="center">
              <Avatar
                src={selectedLang ? buildImageUrl(selectedLang.image_path) : ''}
                sx={{ width: 28, height: 28, bgcolor: selectedLang?.primary_color || 'action.selected' }}
              >
                <LanguageIcon fontSize="small" />
              </Avatar>
              <Box>
                <Typography variant="subtitle1" sx={{ fontWeight: 700, lineHeight: 1.15 }}>
                  {selectedLang?.name || 'Select Major'}
                </Typography>
                <Typography variant="caption" color="text.secondary" sx={{ lineHeight: 1.1 }}>
                  {selectedLang?.code || '-'}
                </Typography>
              </Box>
            </Stack>
            <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
              {editing ? 'Edit Banner' : 'Add Banner'}
            </Typography>
          </Stack>
        </DialogTitle>
        <Divider />
        <DialogContent sx={{ pt: 2 }}>
          <Stack spacing={1.25}>
            <TextField
              select
              label="Major"
              value={data.major}
              onChange={(e) => setData('major', e.target.value)}
              error={Boolean(errors.major)}
              helperText={errors.major}
              fullWidth
              size="small"
            >
              {languages.map((l) => (
                <MenuItem key={`banner-major-${l.code}`} value={l.code}>
                  <Stack direction="row" spacing={1} alignItems="center">
                    <Avatar
                      src={buildImageUrl(l.image_path)}
                      sx={{ width: 22, height: 22, bgcolor: l.primary_color || 'action.selected' }}
                    >
                      <LanguageIcon fontSize="small" />
                    </Avatar>
                    <Typography variant="body2">{l.name}</Typography>
                  </Stack>
                </MenuItem>
              ))}
            </TextField>

            <TextField
              label="Title"
              value={data.title}
              onChange={(e) => setData('title', e.target.value)}
              error={Boolean(errors.title)}
              helperText={errors.title}
              fullWidth
              size="small"
              placeholder="Banner title"
            />

            <TextField
              label="Image URL"
              value={data.image_url}
              onChange={(e) => {
                setData('image_url', e.target.value);
                setData('image', null);
                setImagePreview(e.target.value);
              }}
              error={Boolean(errors.image_url)}
              helperText={errors.image_url}
              fullWidth
              size="small"
              placeholder="/uploads/... or https://..."
            />

            <Paper variant="outlined" sx={{ p: 1.25, borderRadius: 2 }}>
              <Stack direction="row" spacing={1.25} alignItems="center" justifyContent="space-between">
                <Stack direction="row" spacing={1.25} alignItems="center" sx={{ minWidth: 0 }}>
                <Avatar variant="rounded" src={previewImageUrl} sx={{ width: 84, height: 48, bgcolor: 'action.selected', borderRadius: 1 }}>
                  <ImageIcon fontSize="small" />
                </Avatar>
                <Box sx={{ minWidth: 0 }}>
                  <Typography variant="body2" sx={{ fontWeight: 700 }}>
                    Preview
                  </Typography>
                  <Typography variant="caption" color="text.secondary" sx={{ wordBreak: 'break-all' }}>
                    {imagePreview || data.image_url || '-'}
                  </Typography>
                </Box>
                </Stack>
                <Button size="small" variant="outlined" onClick={handleSelectImage}>
                  Upload & Crop (2000×650)
                </Button>
              </Stack>
            </Paper>

            <TextField
              label="Link (optional)"
              value={data.link}
              onChange={(e) => setData('link', e.target.value)}
              error={Boolean(errors.link)}
              helperText={errors.link}
              fullWidth
              size="small"
              placeholder="https://..."
            />
          </Stack>
        </DialogContent>
        <Divider />
        <DialogActions sx={{ p: 2 }}>
          <Button onClick={handleClose} disabled={processing}>
            Cancel
          </Button>
          <Button onClick={handleSubmit} variant="contained" disabled={processing}>
            {editing ? 'Save Changes' : 'Create'}
          </Button>
        </DialogActions>
      </Dialog>

      <Menu
        anchorEl={rowMenu.anchorEl}
        open={rowMenuOpen}
        onClose={closeRowMenu}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
        transformOrigin={{ vertical: 'top', horizontal: 'right' }}
      >
        <MenuItem
          onClick={() => {
            const row = rowMenu.row;
            closeRowMenu();
            if (row) handleOpenEdit(row);
          }}
        >
          <ListItemIcon>
            <EditIcon fontSize="small" />
          </ListItemIcon>
          <ListItemText>Edit</ListItemText>
        </MenuItem>
        <MenuItem
          onClick={() => {
            const row = rowMenu.row;
            closeRowMenu();
            if (row) handleDelete(row);
          }}
        >
          <ListItemIcon>
            <DeleteIcon fontSize="small" />
          </ListItemIcon>
          <ListItemText>Delete</ListItemText>
        </MenuItem>
      </Menu>

      <ImageCropper
        open={imageCropper.open}
        image={imageCropper.image}
        aspect={2000 / 650}
        outputWidth={2000}
        outputHeight={650}
        title="Crop Banner (2000×650)"
        onCropComplete={(blob) => {
          const type = blob.type || 'image/jpeg';
          const baseName = String(imageCropper.fileName || 'banner.jpg').replace(/\.[^/.]+$/, '');
          const file = new File([blob], `${baseName}.jpg`, { type });
          setData('image', file);
          setData('image_url', '');
          setImagePreview(URL.createObjectURL(file));
          setImageCropper({ open: false, image: '', fileType: 'image/jpeg', fileName: 'banner.jpg' });
        }}
        onCancel={() => setImageCropper({ open: false, image: '', fileType: 'image/jpeg', fileName: 'banner.jpg' })}
      />
    </Box>
  );
}

Banners.layout = (page) => <AdminLayout children={page} />;
