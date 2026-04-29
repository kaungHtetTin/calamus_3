import React from 'react';
import { Head, useForm, usePage, router } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import {
  Avatar,
  Box,
  Button,
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
  Chip,
} from '@mui/material';
import { Add as AddIcon, Edit as EditIcon, Delete as DeleteIcon, MoreVert as MoreVertIcon, Language as LanguageIcon } from '@mui/icons-material';

export default function ActivationMessages({ activationMessages, languageOptions }) {
  const { admin_app_url } = usePage().props;
  const rows = Array.isArray(activationMessages) ? activationMessages : [];
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

  const { data, setData, post, patch, processing, errors, reset, clearErrors } = useForm({
    major: '',
    message: '',
  });

  const handleOpenAdd = () => {
    setEditing(null);
    reset();
    clearErrors();
    setData({
      major: languages[0]?.code || '',
      message: '',
    });
    setOpenDialog(true);
  };

  const handleOpenEdit = (row) => {
    setEditing(row);
    clearErrors();
    setData({
      major: row.major || '',
      message: row.message || '',
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
    if (editing) {
      patch(`${admin_app_url}/activation-messages/${editing.id}`, { preserveScroll: true, onSuccess: handleClose });
      return;
    }
    post(`${admin_app_url}/activation-messages`, { preserveScroll: true, onSuccess: handleClose });
  };

  const handleDelete = (row) => {
    if (!confirm('Delete this activation message?')) return;
    router.delete(`${admin_app_url}/activation-messages/${row.id}`, { preserveScroll: true });
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
      <Head title="Activation Messages" />

      <Stack spacing={1.5}>
        <Stack direction="row" justifyContent="space-between" alignItems="center">
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              Activation Messages
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Manage activation messages by language.
            </Typography>
          </Box>
          <Button variant="contained" size="small" startIcon={<AddIcon />} onClick={handleOpenAdd}>
            Add Message
          </Button>
        </Stack>

        <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
          <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
            <Table size="small" sx={{ tableLayout: 'auto', '& .MuiTableCell-root': { px: 1, py: 0.75, verticalAlign: 'top' } }}>
              <TableHead>
                <TableRow>
                  <TableCell sx={{ fontWeight: 700, width: 220 }}>Major</TableCell>
                  <TableCell sx={{ fontWeight: 700, minWidth: 520 }}>Message</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 170 }}>Updated</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 90 }}>Actions</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {rows.map((row) => {
                  const lang = languageByCode.get(String(row.major || '').toLowerCase().trim());
                  return (
                    <TableRow key={`activation-message-${row.id}`} hover>
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
                        <Stack spacing={0.75}>
                          <Typography variant="body2" sx={{ whiteSpace: 'pre-wrap', wordBreak: 'break-word' }}>
                            {row.message}
                          </Typography>
                          <Stack direction="row" spacing={1} alignItems="center">
                            <Chip
                              size="small"
                              label="Copy"
                              variant="outlined"
                              onClick={() => navigator.clipboard.writeText(String(row.message || ''))}
                            />
                          </Stack>
                        </Stack>
                      </TableCell>
                      <TableCell>
                        <Typography variant="body2" color="text.secondary">
                          {row.updated_at ? String(row.updated_at).replace('T', ' ').slice(0, 16) : '-'}
                        </Typography>
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
                    <TableCell colSpan={4}>
                      <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                        No activation messages found.
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
              {editing ? 'Edit Message' : 'Add Message'}
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
                <MenuItem key={`activation-message-major-${l.code}`} value={l.code}>
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
              label="Message"
              value={data.message}
              onChange={(e) => setData('message', e.target.value)}
              error={Boolean(errors.message)}
              helperText={errors.message}
              fullWidth
              size="small"
              multiline
              minRows={4}
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
    </Box>
  );
}

ActivationMessages.layout = (page) => <AdminLayout children={page} />;
