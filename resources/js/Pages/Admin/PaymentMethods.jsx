import React from 'react';
import { Head, useForm, usePage, router } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import {
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
  Checkbox,
  FormControlLabel,
} from '@mui/material';
import { Add as AddIcon, Edit as EditIcon, Delete as DeleteIcon, MoreVert as MoreVertIcon } from '@mui/icons-material';

export default function PaymentMethods({ paymentMethods }) {
  const { admin_app_url } = usePage().props;
  const rows = Array.isArray(paymentMethods) ? paymentMethods : [];
  const [openDialog, setOpenDialog] = React.useState(false);
  const [editing, setEditing] = React.useState(null);
  const [logoPreview, setLogoPreview] = React.useState('');
  const [rowMenu, setRowMenu] = React.useState({ anchorEl: null, row: null });
  const rowMenuOpen = Boolean(rowMenu.anchorEl);

  const { data, setData, post, patch, delete: destroy, processing, errors, reset, clearErrors } = useForm({
    name: '',
    account_name: '',
    account_number: '',
    logo: '',
    active: true,
    sort_order: 0,
  });

  const handleOpenAdd = () => {
    setEditing(null);
    reset();
    clearErrors();
    setData('active', true);
    setOpenDialog(true);
  };

  const handleOpenEdit = (row) => {
    setEditing(row);
    clearErrors();
    setData({
      name: row.name || '',
      account_name: row.account_name || '',
      account_number: row.account_number || '',
      logo: null,
      remove_logo: false,
      active: Boolean(row.active),
      sort_order: Number(row.sort_order ?? 0),
    });
    setLogoPreview(row.logo || '');
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
      patch(`${admin_app_url}/payment-methods/${editing.id}`, {
        preserveScroll: true,
        onSuccess: handleClose,
      });
      return;
    }
    post(`${admin_app_url}/payment-methods`, {
      preserveScroll: true,
      onSuccess: handleClose,
    });
  };

  const handleDelete = (row) => {
    if (!confirm(`Delete payment method "${row.name}"?`)) return;
    router.delete(`${admin_app_url}/payment-methods/${row.id}`, { preserveScroll: true });
  };

  const openRowMenu = (event, row) => {
    setRowMenu({ anchorEl: event.currentTarget, row });
  };

  const closeRowMenu = () => {
    setRowMenu({ anchorEl: null, row: null });
  };

  const handleSelectLogo = () => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = (e) => {
      const file = e.target.files?.[0];
      if (!file) return;
      setData('logo', file);
      const url = URL.createObjectURL(file);
      setLogoPreview(url);
    };
    input.click();
  };

  const handleRemoveLogo = () => {
    setData('logo', null);
    setData('remove_logo', true);
    setLogoPreview('');
  };

  return (
    <Box>
      <Head title="Payment Methods" />

      <Stack spacing={1.5}>
        <Stack direction="row" justifyContent="space-between" alignItems="center">
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              Payment Methods
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Manage available bank/wallet methods for users to pay.
            </Typography>
          </Box>
          <Button variant="contained" size="small" startIcon={<AddIcon />} onClick={handleOpenAdd}>
            Add Method
          </Button>
        </Stack>

        <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
          <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
            <Table size="small" sx={{ tableLayout: 'auto', '& .MuiTableCell-root': { px: 1, py: 0.75, verticalAlign: 'top' } }}>
              <TableHead>
                <TableRow>
                  <TableCell sx={{ fontWeight: 700, minWidth: 160 }}>Name</TableCell>
                  <TableCell sx={{ fontWeight: 700, minWidth: 180 }}>Account Name</TableCell>
                  <TableCell sx={{ fontWeight: 700, minWidth: 140 }}>Account Number</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 120 }}>Logo</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 90 }}>Active</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 100 }}>Sort</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 110 }}>Actions</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {rows.map((row) => (
                  <TableRow key={`pm-${row.id}`} hover>
                    <TableCell>
                      <Typography variant="body2" sx={{ fontWeight: 700 }}>
                        {row.name}
                      </Typography>
                    </TableCell>
                    <TableCell>
                      <Typography variant="body2">{row.account_name}</Typography>
                    </TableCell>
                    <TableCell>
                      <Typography variant="body2">{row.account_number}</Typography>
                    </TableCell>
                    <TableCell>
                      {row.logo ? (
                        <Box
                          component="img"
                          src={row.logo}
                          alt={row.name}
                          sx={{ width: 44, height: 44, objectFit: 'contain', borderRadius: 1, border: '1px solid', borderColor: 'divider' }}
                        />
                      ) : (
                        <Typography variant="body2" color="text.secondary">
                          -
                        </Typography>
                      )}
                    </TableCell>
                    <TableCell>
                      <Chip size="small" color={row.active ? 'success' : 'default'} label={row.active ? 'Active' : 'Inactive'} />
                    </TableCell>
                    <TableCell>
                      <Typography variant="body2">{row.sort_order ?? 0}</Typography>
                    </TableCell>
                    <TableCell>
                      <IconButton size="small" aria-label="actions" onClick={(e) => openRowMenu(e, row)}>
                        <MoreVertIcon fontSize="small" />
                      </IconButton>
                    </TableCell>
                  </TableRow>
                ))}
                {rows.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={7}>
                      <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                        No payment methods found.
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
        <DialogTitle sx={{ fontWeight: 700 }}>{editing ? 'Edit Payment Method' : 'Add Payment Method'}</DialogTitle>
        <Divider />
        <DialogContent sx={{ pt: 2 }}>
          <Stack spacing={1.25}>
            <Stack spacing={0.5}>
              <Typography variant="subtitle2">Logo</Typography>
              <Stack direction="row" spacing={1.25} alignItems="center">
                <Box
                  onClick={handleSelectLogo}
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
                  {logoPreview ? (
                    <Box component="img" src={logoPreview} alt="logo preview" sx={{ width: '100%', height: '100%', objectFit: 'contain' }} />
                  ) : (
                    <Typography variant="caption" color="text.secondary">Select</Typography>
                  )}
                </Box>
                <Stack direction="row" spacing={1}>
                  <Button variant="outlined" size="small" onClick={handleSelectLogo}>Change</Button>
                  <Button
                    variant="text"
                    size="small"
                    color="error"
                    onClick={handleRemoveLogo}
                    disabled={!logoPreview}
                  >
                    Remove
                  </Button>
                </Stack>
              </Stack>
              <Typography variant="caption" color="text.secondary">Square image recommended. Cropped to 1:1.</Typography>
              {errors.logo ? (
                <Typography variant="caption" color="error.main">{errors.logo}</Typography>
              ) : null}
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
              label="Account Name"
              value={data.account_name}
              onChange={(e) => setData('account_name', e.target.value)}
              error={Boolean(errors.account_name)}
              helperText={errors.account_name}
              fullWidth
              size="small"
            />
            <TextField
              label="Account Number"
              value={data.account_number}
              onChange={(e) => setData('account_number', e.target.value)}
              error={Boolean(errors.account_number)}
              helperText={errors.account_number}
              fullWidth
              size="small"
            />
            <Stack direction="row" spacing={2}>
              <FormControlLabel
                control={<Checkbox checked={Boolean(data.active)} onChange={(e) => setData('active', e.target.checked)} />}
                label="Active"
              />
              <TextField
                label="Sort Order"
                type="number"
                value={data.sort_order}
                onChange={(e) => setData('sort_order', Number(e.target.value))}
                error={Boolean(errors.sort_order)}
                helperText={errors.sort_order}
                size="small"
                sx={{ width: 160 }}
              />
            </Stack>
          </Stack>
        </DialogContent>
        <Divider />
        <DialogActions sx={{ p: 2 }}>
          <Button onClick={handleClose} disabled={processing}>Cancel</Button>
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

PaymentMethods.layout = (page) => <AdminLayout children={page} />;
