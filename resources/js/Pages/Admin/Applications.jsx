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
  FormControlLabel,
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
  Checkbox,
} from '@mui/material';
import { Add as AddIcon, Edit as EditIcon, Delete as DeleteIcon, MoreVert as MoreVertIcon, Language as LanguageIcon, OpenInNew as OpenInNewIcon } from '@mui/icons-material';

export default function Applications({ apps, languageOptions }) {
  const { admin_app_url } = usePage().props;
  const rows = Array.isArray(apps) ? apps : [];
  const languages = Array.isArray(languageOptions) ? languageOptions : [];
  const appBaseUrl = admin_app_url.replace(/\/admin\/?$/, '');

  const [openDialog, setOpenDialog] = React.useState(false);
  const [editing, setEditing] = React.useState(null);
  const [rowMenu, setRowMenu] = React.useState({ anchorEl: null, row: null });
  const rowMenuOpen = Boolean(rowMenu.anchorEl);

  const [iconPreview, setIconPreview] = React.useState('');
  const [coverPreview, setCoverPreview] = React.useState('');
  const [coverCropper, setCoverCropper] = React.useState({ open: false, image: '', fileType: 'image/jpeg', fileName: 'cover.jpg' });

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
    name: '',
    description: '',
    url: '',
    major: '',
    type: '',
    show_on: 1,
    active_course: 0,
    click: 0,
    student_learning: '',
    package_id: '',
    platform: 'android',
    latest_version_code: '',
    latest_version_name: '',
    min_version_code: '',
    update_message: '',
    force_update: false,
    icon: null,
    cover: null,
    remove_icon: false,
    remove_cover: false,
  });

  const handleOpenAdd = () => {
    setEditing(null);
    reset();
    clearErrors();
    setIconPreview('');
    setCoverPreview('');
    setData({
      name: '',
      description: '',
      url: '',
      major: languages[0]?.code || '',
      type: '',
      show_on: 1,
      active_course: 0,
      click: 0,
      student_learning: '',
      package_id: '',
      platform: 'android',
      latest_version_code: '',
      latest_version_name: '',
      min_version_code: '',
      update_message: '',
      force_update: false,
      icon: null,
      cover: null,
      remove_icon: false,
      remove_cover: false,
    });
    setOpenDialog(true);
  };

  const handleOpenEdit = (row) => {
    setEditing(row);
    clearErrors();
    setIconPreview(row.icon || '');
    setCoverPreview(row.cover || '');
    setData({
      name: row.name || '',
      description: row.description || '',
      url: row.url || '',
      major: row.major || '',
      type: row.type || '',
      show_on: Number(row.show_on ?? 0),
      active_course: Number(row.active_course ?? 0),
      click: Number(row.click ?? 0),
      student_learning: row.student_learning || '',
      package_id: row.package_id || '',
      platform: row.platform || 'android',
      latest_version_code: row.latest_version_code ?? '',
      latest_version_name: row.latest_version_name ?? '',
      min_version_code: row.min_version_code ?? '',
      update_message: row.update_message ?? '',
      force_update: Boolean(row.force_update),
      icon: null,
      cover: null,
      remove_icon: false,
      remove_cover: false,
    });
    setOpenDialog(true);
  };

  const handleClose = () => {
    setOpenDialog(false);
    setEditing(null);
    reset();
    clearErrors();
    setIconPreview('');
    setCoverPreview('');
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (editing) {
      patch(`${admin_app_url}/apps/${editing.id}`, { preserveScroll: true, onSuccess: handleClose });
      return;
    }
    post(`${admin_app_url}/apps`, { preserveScroll: true, onSuccess: handleClose });
  };

  const handleDelete = (row) => {
    if (!confirm(`Delete application \"${row.name}\"?`)) return;
    router.delete(`${admin_app_url}/apps/${row.id}`, { preserveScroll: true });
  };

  const openRowMenu = (event, row) => setRowMenu({ anchorEl: event.currentTarget, row });
  const closeRowMenu = () => setRowMenu({ anchorEl: null, row: null });

  const handleSelectImage = (field) => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = (e) => {
      const file = e.target.files?.[0];
      if (!file) return;
      if (field === 'cover') {
        const reader = new FileReader();
        reader.onload = () => {
          setCoverCropper({
            open: true,
            image: String(reader.result || ''),
            fileType: file.type || 'image/jpeg',
            fileName: file.name || 'cover.jpg',
          });
        };
        reader.readAsDataURL(file);
        return;
      }

      setData(field, file);
      const url = URL.createObjectURL(file);
      if (field === 'icon') setIconPreview(url);
      if (field === 'icon') setData('remove_icon', false);
    };
    input.click();
  };

  const handleRemoveImage = (field) => {
    if (field === 'icon') {
      setData('icon', null);
      setData('remove_icon', true);
      setIconPreview('');
    }
    if (field === 'cover') {
      setData('cover', null);
      setData('remove_cover', true);
      setCoverPreview('');
    }
  };

  const selectedLang = languageByCode.get(String(data.major || '').toLowerCase().trim());

  return (
    <Box>
      <Head title="Applications" />

      <Stack spacing={1.5}>
        <Stack direction="row" justifyContent="space-between" alignItems="center">
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              Applications
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Manage apps shown to users and app update settings.
            </Typography>
          </Box>
          <Button variant="contained" size="small" startIcon={<AddIcon />} onClick={handleOpenAdd}>
            Add App
          </Button>
        </Stack>

        <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
          <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
            <Table size="small" sx={{ tableLayout: 'auto', '& .MuiTableCell-root': { px: 1, py: 0.75, verticalAlign: 'top' } }}>
              <TableHead>
                <TableRow>
                  <TableCell sx={{ fontWeight: 700, width: 70 }}>Icon</TableCell>
                  <TableCell sx={{ fontWeight: 700, minWidth: 260 }}>App</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 180 }}>Major</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 110 }}>Show</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 160 }}>Platform</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 180 }}>Latest</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 110 }}>Force</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 90 }}>Actions</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {rows.map((row) => {
                  const lang = languageByCode.get(String(row.major || '').toLowerCase().trim());
                  const isShown = Number(row.show_on ?? 0) > 0;
                  return (
                    <TableRow key={`app-${row.id}`} hover>
                      <TableCell>
                        {row.icon ? (
                          <Box
                            component="img"
                            src={row.icon}
                            alt="icon"
                            sx={{ width: 36, height: 36, borderRadius: 1, objectFit: 'contain', border: '1px solid', borderColor: 'divider' }}
                          />
                        ) : (
                          <Avatar sx={{ width: 36, height: 36, bgcolor: 'action.selected' }}>A</Avatar>
                        )}
                      </TableCell>
                      <TableCell>
                        <Stack spacing={0.25}>
                          <Typography variant="body2" sx={{ fontWeight: 700, lineHeight: 1.15 }}>
                            {row.name}
                          </Typography>
                          <Stack direction="row" spacing={0.75} alignItems="center">
                            <Typography variant="caption" color="text.secondary" sx={{ lineHeight: 1.1 }}>
                              {row.package_id || '-'}
                            </Typography>
                            {row.url ? (
                              <IconButton size="small" aria-label="open" onClick={() => window.open(row.url, '_blank')}>
                                <OpenInNewIcon fontSize="inherit" />
                              </IconButton>
                            ) : null}
                          </Stack>
                        </Stack>
                      </TableCell>
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
                        <Chip size="small" label={isShown ? 'Shown' : 'Hidden'} color={isShown ? 'success' : 'default'} />
                      </TableCell>
                      <TableCell>
                        <Typography variant="body2">{row.platform || '-'}</Typography>
                        <Typography variant="caption" color="text.secondary">
                          min {row.min_version_code ?? '-'}
                        </Typography>
                      </TableCell>
                      <TableCell>
                        <Typography variant="body2">{row.latest_version_name || '-'}</Typography>
                        <Typography variant="caption" color="text.secondary">
                          code {row.latest_version_code ?? '-'}
                        </Typography>
                      </TableCell>
                      <TableCell>
                        <Chip size="small" label={row.force_update ? 'Yes' : 'No'} color={row.force_update ? 'error' : 'default'} />
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
                    <TableCell colSpan={8}>
                      <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                        No apps found.
                      </Typography>
                    </TableCell>
                  </TableRow>
                ) : null}
              </TableBody>
            </Table>
          </TableContainer>
        </Paper>
      </Stack>

      <Dialog open={openDialog} onClose={handleClose} maxWidth="md" fullWidth>
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
              {editing ? 'Edit App' : 'Add App'}
            </Typography>
          </Stack>
        </DialogTitle>
        <Divider />
        <DialogContent sx={{ pt: 2 }}>
          <Stack spacing={1.25}>
            <Stack direction={{ xs: 'column', md: 'row' }} spacing={1.25} alignItems={{ xs: 'stretch', md: 'center' }}>
              <Stack spacing={0.5} sx={{ width: { xs: '100%', md: 220 } }}>
                <Typography variant="subtitle2">Icon</Typography>
                <Stack direction="row" spacing={1.25} alignItems="center">
                  <Box
                    onClick={() => handleSelectImage('icon')}
                    sx={{
                      width: 72,
                      height: 72,
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
                    {iconPreview ? (
                      <Box component="img" src={iconPreview} alt="icon preview" sx={{ width: '100%', height: '100%', objectFit: 'contain' }} />
                    ) : (
                      <Typography variant="caption" color="text.secondary">Select</Typography>
                    )}
                  </Box>
                  <Stack direction="row" spacing={1}>
                    <Button variant="outlined" size="small" onClick={() => handleSelectImage('icon')}>Change</Button>
                    <Button variant="text" size="small" color="error" onClick={() => handleRemoveImage('icon')} disabled={!iconPreview}>
                      Remove
                    </Button>
                  </Stack>
                </Stack>
                {errors.icon ? <Typography variant="caption" color="error.main">{errors.icon}</Typography> : null}
              </Stack>

              <Stack spacing={0.5} sx={{ width: { xs: '100%', md: 320 } }}>
                <Typography variant="subtitle2">Cover (16:9)</Typography>
                <Stack direction="row" spacing={1.25} alignItems="center">
                  <Box
                    onClick={() => handleSelectImage('cover')}
                    sx={{
                      width: 160,
                      height: 72,
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
                    {coverPreview ? (
                      <Box component="img" src={coverPreview} alt="cover preview" sx={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                    ) : (
                      <Typography variant="caption" color="text.secondary">Select</Typography>
                    )}
                  </Box>
                  <Stack direction="row" spacing={1}>
                    <Button variant="outlined" size="small" onClick={() => handleSelectImage('cover')}>Change</Button>
                    <Button variant="text" size="small" color="error" onClick={() => handleRemoveImage('cover')} disabled={!coverPreview}>
                      Remove
                    </Button>
                  </Stack>
                </Stack>
                {errors.cover ? <Typography variant="caption" color="error.main">{errors.cover}</Typography> : null}
              </Stack>
            </Stack>

            <Stack direction={{ xs: 'column', md: 'row' }} spacing={1.25}>
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
                  <MenuItem key={`app-major-${l.code}`} value={l.code}>
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
                select
                label="Platform"
                value={data.platform}
                onChange={(e) => setData('platform', e.target.value)}
                error={Boolean(errors.platform)}
                helperText={errors.platform}
                fullWidth
                size="small"
              >
                <MenuItem value="android">Android</MenuItem>
                <MenuItem value="ios">iOS</MenuItem>
              </TextField>
              <TextField
                label="Package ID"
                value={data.package_id}
                onChange={(e) => setData('package_id', e.target.value)}
                error={Boolean(errors.package_id)}
                helperText={errors.package_id}
                fullWidth
                size="small"
              />
            </Stack>

            <TextField
              label="Name"
              value={data.name}
              onChange={(e) => setData('name', e.target.value)}
              error={Boolean(errors.name)}
              helperText={errors.name}
              fullWidth
              size="small"
            />

            <TextField
              label="URL"
              value={data.url}
              onChange={(e) => setData('url', e.target.value)}
              error={Boolean(errors.url)}
              helperText={errors.url}
              fullWidth
              size="small"
            />

            <TextField
              label="Description"
              value={data.description}
              onChange={(e) => setData('description', e.target.value)}
              error={Boolean(errors.description)}
              helperText={errors.description}
              fullWidth
              size="small"
              multiline
              minRows={2}
            />

            <Stack direction={{ xs: 'column', md: 'row' }} spacing={1.25}>
              <TextField
                label="Type"
                value={data.type}
                onChange={(e) => setData('type', e.target.value)}
                error={Boolean(errors.type)}
                helperText={errors.type}
                fullWidth
                size="small"
              />
              <TextField
                label="Show On"
                type="number"
                value={data.show_on}
                onChange={(e) => setData('show_on', Number(e.target.value))}
                error={Boolean(errors.show_on)}
                helperText={errors.show_on}
                fullWidth
                size="small"
              />
              <TextField
                label="Active Course"
                type="number"
                value={data.active_course}
                onChange={(e) => setData('active_course', Number(e.target.value))}
                error={Boolean(errors.active_course)}
                helperText={errors.active_course}
                fullWidth
                size="small"
              />
              <TextField
                label="Click"
                type="number"
                value={data.click}
                onChange={(e) => setData('click', Number(e.target.value))}
                error={Boolean(errors.click)}
                helperText={errors.click}
                fullWidth
                size="small"
              />
            </Stack>

            <TextField
              label="Student Learning"
              value={data.student_learning}
              onChange={(e) => setData('student_learning', e.target.value)}
              error={Boolean(errors.student_learning)}
              helperText={errors.student_learning}
              fullWidth
              size="small"
            />

            <Divider />

            <Typography variant="subtitle2" sx={{ fontWeight: 700 }}>
              Update Settings
            </Typography>

            <Stack direction={{ xs: 'column', md: 'row' }} spacing={1.25}>
              <TextField
                label="Latest Version Code"
                type="number"
                value={data.latest_version_code}
                onChange={(e) => setData('latest_version_code', e.target.value === '' ? '' : Number(e.target.value))}
                error={Boolean(errors.latest_version_code)}
                helperText={errors.latest_version_code}
                fullWidth
                size="small"
              />
              <TextField
                label="Latest Version Name"
                value={data.latest_version_name}
                onChange={(e) => setData('latest_version_name', e.target.value)}
                error={Boolean(errors.latest_version_name)}
                helperText={errors.latest_version_name}
                fullWidth
                size="small"
              />
              <TextField
                label="Min Version Code"
                type="number"
                value={data.min_version_code}
                onChange={(e) => setData('min_version_code', e.target.value === '' ? '' : Number(e.target.value))}
                error={Boolean(errors.min_version_code)}
                helperText={errors.min_version_code}
                fullWidth
                size="small"
              />
            </Stack>

            <FormControlLabel
              control={<Checkbox checked={Boolean(data.force_update)} onChange={(e) => setData('force_update', e.target.checked)} />}
              label="Force Update"
            />

            <TextField
              label="Update Message"
              value={data.update_message}
              onChange={(e) => setData('update_message', e.target.value)}
              error={Boolean(errors.update_message)}
              helperText={errors.update_message}
              fullWidth
              size="small"
              multiline
              minRows={2}
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
        open={coverCropper.open}
        image={coverCropper.image}
        aspect={16 / 9}
        title="Crop Cover (16:9)"
        onCropComplete={(blob) => {
          const type = coverCropper.fileType || blob.type || 'image/jpeg';
          const file = new File([blob], coverCropper.fileName || 'cover.jpg', { type });
          setData('cover', file);
          setData('remove_cover', false);
          setCoverPreview(URL.createObjectURL(file));
          setCoverCropper({ open: false, image: '', fileType: 'image/jpeg', fileName: 'cover.jpg' });
        }}
        onCancel={() => setCoverCropper({ open: false, image: '', fileType: 'image/jpeg', fileName: 'cover.jpg' })}
      />
    </Box>
  );
}

Applications.layout = (page) => <AdminLayout children={page} />;
