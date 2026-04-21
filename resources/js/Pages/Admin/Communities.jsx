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
  FormControlLabel,
  IconButton,
  ListItemIcon,
  ListItemText,
  Menu,
  MenuItem,
  Paper,
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
} from '@mui/material';
import {
  Add as AddIcon,
  Delete as DeleteIcon,
  Edit as EditIcon,
  Language as LanguageIcon,
  MoreVert as MoreVertIcon,
} from '@mui/icons-material';

const defaultForm = {
  major: '',
  name: '',
  platform: '',
  url: '',
  sort_order: 0,
  active: true,
};

export default function Communities({ communities, languageOptions }) {
  const { admin_app_url } = usePage().props;
  const rows = Array.isArray(communities) ? communities : [];
  const languages = Array.isArray(languageOptions) ? languageOptions : [];
  const appBaseUrl = admin_app_url.replace(/\/admin\/?$/, '');

  const [openDialog, setOpenDialog] = React.useState(false);
  const [editing, setEditing] = React.useState(null);
  const [rowMenu, setRowMenu] = React.useState({ anchorEl: null, row: null });
  const rowMenuOpen = Boolean(rowMenu.anchorEl);

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

  const { data, setData, post, patch, processing, errors, reset, clearErrors } = useForm(defaultForm);

  const handleOpenAdd = () => {
    setEditing(null);
    reset();
    clearErrors();
    setData({
      ...defaultForm,
      major: languages[0]?.code || '',
      active: true,
      sort_order: 0,
    });
    setOpenDialog(true);
  };

  const handleOpenEdit = (row) => {
    setEditing(row);
    clearErrors();
    setData({
      major: row.major || '',
      name: row.name || '',
      platform: row.platform || '',
      url: row.url || '',
      sort_order: Number(row.sort_order || 0),
      active: Boolean(row.active),
    });
    setOpenDialog(true);
  };

  const handleClose = () => {
    setOpenDialog(false);
    setEditing(null);
    reset();
    clearErrors();
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    const payload = {
      ...data,
      sort_order: Number(data.sort_order || 0),
      active: data.active ? 1 : 0,
    };

    if (editing) {
      patch(`${admin_app_url}/communities/${editing.id}`, { data: payload, preserveScroll: true, onSuccess: handleClose });
      return;
    }
    post(`${admin_app_url}/communities`, { data: payload, preserveScroll: true, onSuccess: handleClose });
  };

  const handleDelete = (row) => {
    if (!confirm(`Delete "${row.name}"?`)) return;
    router.delete(`${admin_app_url}/communities/${row.id}`, { preserveScroll: true });
  };

  const openRowMenu = (event, row) => {
    setRowMenu({ anchorEl: event.currentTarget, row });
  };

  const closeRowMenu = () => {
    setRowMenu({ anchorEl: null, row: null });
  };

  const selectedLang = languageByCode.get(String(data.major || '').toLowerCase().trim());

  return (
    <Box>
      <Head title="Communities" />

      <Stack spacing={1.5}>
        <Stack direction="row" justifyContent="space-between" alignItems="center">
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              Communities
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Manage community links shown in the app.
            </Typography>
          </Box>
          <Button variant="contained" size="small" startIcon={<AddIcon />} onClick={handleOpenAdd}>
            Add Community
          </Button>
        </Stack>

        <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
          <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2, overflowX: 'hidden' }}>
            <Table
              size="small"
              sx={{
                tableLayout: 'auto',
                width: '100%',
                '& .MuiTableCell-root': {
                  px: 0.75,
                  py: 0.5,
                  verticalAlign: 'top',
                },
              }}
            >
              <TableHead>
                <TableRow>
                  <TableCell sx={{ fontWeight: 700, whiteSpace: 'nowrap' }}>Major</TableCell>
                  <TableCell sx={{ fontWeight: 700 }}>Name</TableCell>
                  <TableCell sx={{ fontWeight: 700, whiteSpace: 'nowrap' }}>Platform</TableCell>
                  <TableCell sx={{ fontWeight: 700 }}>URL</TableCell>
                  <TableCell sx={{ fontWeight: 700, whiteSpace: 'nowrap' }}>Sort</TableCell>
                  <TableCell sx={{ fontWeight: 700, whiteSpace: 'nowrap' }}>Status</TableCell>
                  <TableCell sx={{ fontWeight: 700, whiteSpace: 'nowrap' }}>Actions</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {rows.map((row) => {
                  const lang = languageByCode.get(String(row.major || '').toLowerCase().trim());
                  return (
                    <TableRow key={`com-${row.id}`} hover>
                      <TableCell sx={{ width: 150 }}>
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
                      <TableCell sx={{ maxWidth: 260 }}>
                        <Typography
                          variant="body2"
                          sx={{
                            fontWeight: 600,
                            whiteSpace: 'normal',
                            overflowWrap: 'anywhere',
                            wordBreak: 'break-word',
                          }}
                        >
                          {row.name}
                        </Typography>
                      </TableCell>
                      <TableCell sx={{ width: 120, whiteSpace: 'nowrap' }}>
                        <Typography variant="body2" color="text.secondary">
                          {row.platform || '-'}
                        </Typography>
                      </TableCell>
                      <TableCell sx={{ maxWidth: 520 }}>
                        <Stack spacing={0.25}>
                          <Typography
                            variant="body2"
                            sx={{
                              whiteSpace: 'normal',
                              overflowWrap: 'anywhere',
                              wordBreak: 'break-word',
                            }}
                          >
                            {row.url}
                          </Typography>
                          <Stack direction="row" spacing={0.75} alignItems="center" flexWrap="wrap" useFlexGap>
                            <Chip size="small" label="Open" variant="outlined" onClick={() => window.open(row.url, '_blank')} />
                            <Chip
                              size="small"
                              label="Copy"
                              variant="outlined"
                              onClick={() => navigator.clipboard.writeText(String(row.url || ''))}
                            />
                          </Stack>
                        </Stack>
                      </TableCell>
                      <TableCell sx={{ width: 60, whiteSpace: 'nowrap' }}>
                        <Typography variant="body2" color="text.secondary">
                          {Number(row.sort_order || 0)}
                        </Typography>
                      </TableCell>
                      <TableCell sx={{ width: 90, whiteSpace: 'nowrap' }}>
                        <Chip size="small" label={row.active ? 'Active' : 'Inactive'} color={row.active ? 'success' : 'default'} variant="outlined" />
                      </TableCell>
                      <TableCell sx={{ width: 60, whiteSpace: 'nowrap' }}>
                        <IconButton size="small" aria-label="actions" onClick={(e) => openRowMenu(e, row)}>
                          <MoreVertIcon fontSize="small" />
                        </IconButton>
                      </TableCell>
                    </TableRow>
                  );
                })}
                {rows.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={7}>
                      <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                        No communities found.
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
              {editing ? 'Edit Community' : 'Add Community'}
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
                <MenuItem key={`com-major-${l.code}`} value={l.code}>
                  <Stack direction="row" spacing={1} alignItems="center">
                    <Avatar
                      src={buildImageUrl(l.image_path)}
                      sx={{ width: 22, height: 22, bgcolor: l.primary_color || 'action.selected' }}
                    >
                      <LanguageIcon fontSize="small" />
                    </Avatar>
                    <Box>
                      <Typography variant="body2" sx={{ fontWeight: 700, lineHeight: 1.1 }}>
                        {l.name}
                      </Typography>
                      <Typography variant="caption" color="text.secondary" sx={{ lineHeight: 1.05 }}>
                        {l.code}
                      </Typography>
                    </Box>
                  </Stack>
                </MenuItem>
              ))}
            </TextField>

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
              label="Platform"
              value={data.platform}
              onChange={(e) => setData('platform', e.target.value)}
              error={Boolean(errors.platform)}
              helperText={errors.platform || 'Examples: facebook, youtube, tiktok'}
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
              label="Sort Order"
              type="number"
              value={data.sort_order}
              onChange={(e) => setData('sort_order', e.target.value)}
              error={Boolean(errors.sort_order)}
              helperText={errors.sort_order}
              fullWidth
              size="small"
              inputProps={{ min: 0 }}
            />

            <FormControlLabel
              control={<Switch checked={Boolean(data.active)} onChange={(e) => setData('active', e.target.checked)} />}
              label="Active"
            />
          </Stack>
        </DialogContent>
        <Divider />
        <DialogActions sx={{ px: 3, py: 2 }}>
          <Button onClick={handleClose} disabled={processing}>
            Cancel
          </Button>
          <Button variant="contained" onClick={handleSubmit} disabled={processing}>
            {editing ? 'Save Changes' : 'Create'}
          </Button>
        </DialogActions>
      </Dialog>

      <Menu
        anchorEl={rowMenu.anchorEl}
        open={rowMenuOpen}
        onClose={closeRowMenu}
        PaperProps={{
          sx: {
            minWidth: 160,
          },
        }}
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

Communities.layout = (page) => <AdminLayout children={page} />;
