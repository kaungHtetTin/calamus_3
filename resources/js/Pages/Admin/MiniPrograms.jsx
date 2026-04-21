import React from 'react';
import { Head, useForm, usePage, router } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
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
import { Add as AddIcon, Edit as EditIcon, Delete as DeleteIcon, MoreVert as MoreVertIcon, Language as LanguageIcon, OpenInNew as OpenInNewIcon } from '@mui/icons-material';

export default function MiniPrograms({ miniPrograms, languageOptions }) {
  const { admin_app_url } = usePage().props;
  const rows = Array.isArray(miniPrograms) ? miniPrograms : [];
  const languages = Array.isArray(languageOptions) ? languageOptions : [];
  const appBaseUrl = admin_app_url.replace(/\/admin\/?$/, '');

  const [openDialog, setOpenDialog] = React.useState(false);
  const [editing, setEditing] = React.useState(null);
  const [rowMenu, setRowMenu] = React.useState({ anchorEl: null, row: null });
  const rowMenuOpen = Boolean(rowMenu.anchorEl);

  const [imagePreview, setImagePreview] = React.useState('');

  const buildImageUrl = (path) => {
    if (!path) return '';
    if (String(path).startsWith('http://') || String(path).startsWith('https://')) return path;
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
    major: '',
    title: '',
    link_url: '',
    image_url: '',
    image: null,
  });

  const handleOpenAdd = () => {
    setEditing(null);
    reset();
    clearErrors();
    setImagePreview('');
    setData({
      major: languages[0]?.code || '',
      title: '',
      link_url: '',
      image_url: '',
      image: null,
    });
    setOpenDialog(true);
  };

  const handleOpenEdit = (row) => {
    setEditing(row);
    clearErrors();
    setImagePreview(row.image_url || '');
    setData({
      major: row.major || '',
      title: row.title || '',
      link_url: row.link_url || '',
      image_url: row.image_url || '',
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
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (editing) {
      patch(`${admin_app_url}/mini-programs/${editing.id}`, { preserveScroll: true, onSuccess: handleClose });
      return;
    }
    post(`${admin_app_url}/mini-programs`, { preserveScroll: true, onSuccess: handleClose });
  };

  const handleDelete = (row) => {
    if (!confirm(`Delete mini program "${row.title}"?`)) return;
    router.delete(`${admin_app_url}/mini-programs/${row.id}`, { preserveScroll: true });
  };

  const openRowMenu = (event, row) => setRowMenu({ anchorEl: event.currentTarget, row });
  const closeRowMenu = () => setRowMenu({ anchorEl: null, row: null });

  const handleSelectImage = () => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = (e) => {
      const file = e.target.files?.[0];
      if (!file) return;
      setData('image', file);
      setData('image_url', '');
      setImagePreview(URL.createObjectURL(file));
    };
    input.click();
  };

  const selectedLang = languageByCode.get(String(data.major || '').toLowerCase().trim());

  return (
    <Box>
      <Head title="Mini Programs" />

      <Stack spacing={1.5}>
        <Stack direction="row" justifyContent="space-between" alignItems="center">
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              Mini Programs
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Manage mini program links and images by language.
            </Typography>
          </Box>
          <Button variant="contained" size="small" startIcon={<AddIcon />} onClick={handleOpenAdd}>
            Add Mini Program
          </Button>
        </Stack>

        <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
          <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
            <Table size="small" sx={{ tableLayout: 'auto', '& .MuiTableCell-root': { px: 1, py: 0.75, verticalAlign: 'top' } }}>
              <TableHead>
                <TableRow>
                  <TableCell sx={{ fontWeight: 700, width: 220 }}>Major</TableCell>
                  <TableCell sx={{ fontWeight: 700, minWidth: 260 }}>Title</TableCell>
                  <TableCell sx={{ fontWeight: 700, minWidth: 360 }}>Link</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 90 }}>Image</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 90 }}>Actions</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {rows.map((row) => {
                  const lang = languageByCode.get(String(row.major || '').toLowerCase().trim());
                  const imageUrl = buildImageUrl(row.image_url);
                  return (
                    <TableRow key={`mp-${row.id}`} hover>
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
                          {row.title}
                        </Typography>
                      </TableCell>
                      <TableCell>
                        <Stack direction="row" spacing={1} alignItems="center">
                          <Typography variant="body2" sx={{ wordBreak: 'break-all' }}>
                            {row.link_url}
                          </Typography>
                          <IconButton size="small" aria-label="open" onClick={() => window.open(row.link_url, '_blank')}>
                            <OpenInNewIcon fontSize="inherit" />
                          </IconButton>
                        </Stack>
                      </TableCell>
                      <TableCell>
                        {imageUrl ? (
                          <Box
                            component="img"
                            src={imageUrl}
                            alt="mini program"
                            sx={{ width: 44, height: 44, objectFit: 'cover', borderRadius: 1, border: '1px solid', borderColor: 'divider' }}
                          />
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
                        No mini programs found.
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
              {editing ? 'Edit Mini Program' : 'Add Mini Program'}
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
                <MenuItem key={`mp-major-${l.code}`} value={l.code}>
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
            />

            <TextField
              label="Link URL"
              value={data.link_url}
              onChange={(e) => setData('link_url', e.target.value)}
              error={Boolean(errors.link_url)}
              helperText={errors.link_url}
              fullWidth
              size="small"
            />

            <Stack spacing={0.5}>
              <Typography variant="subtitle2">Image</Typography>
              <Stack direction="row" spacing={1.25} alignItems="center">
                <Box
                  onClick={handleSelectImage}
                  sx={{
                    width: 88,
                    height: 88,
                    borderRadius: 1.5,
                    border: '1px solid',
                    borderColor: 'divider',
                    bgcolor: 'background.default',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    overflow: 'hidden',
                    cursor: 'pointer',
                  }}
                >
                  {imagePreview || data.image_url ? (
                    <Box
                      component="img"
                      src={imagePreview || buildImageUrl(data.image_url)}
                      alt="preview"
                      sx={{ width: '100%', height: '100%', objectFit: 'cover' }}
                    />
                  ) : (
                    <Typography variant="caption" color="text.secondary">
                      Select
                    </Typography>
                  )}
                </Box>
                <Stack spacing={0.75} sx={{ flex: 1 }}>
                  <Button variant="outlined" size="small" onClick={handleSelectImage}>
                    Upload Image
                  </Button>
                  <TextField
                    label="Or Image URL"
                    value={data.image_url}
                    onChange={(e) => {
                      setData('image_url', e.target.value);
                      setData('image', null);
                      setImagePreview('');
                    }}
                    error={Boolean(errors.image_url)}
                    helperText={errors.image_url}
                    fullWidth
                    size="small"
                  />
                </Stack>
              </Stack>
              {errors.image ? (
                <Typography variant="caption" color="error.main">
                  {errors.image}
                </Typography>
              ) : null}
            </Stack>
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
    </Box>
  );
}

MiniPrograms.layout = (page) => <AdminLayout children={page} />;
