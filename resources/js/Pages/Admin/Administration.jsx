import React, { useState } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import {
  Box,
  Typography,
  Paper,
  Stack,
  Button,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  IconButton,
  Menu,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  TextField,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Checkbox,
  ListItemText,
  OutlinedInput,
  Chip,
  Avatar,
  Tooltip,
  Alert,
  Snackbar,
  Grid,
  Divider,
  useTheme,
} from '@mui/material';
import {
  Add as AddIcon,
  MoreVert as MoreVertIcon,
  Shield as ShieldIcon,
  Language as LanguageIcon,
} from '@mui/icons-material';

const ITEM_HEIGHT = 48;
const ITEM_PADDING_TOP = 8;
const MenuProps = {
  PaperProps: {
    style: {
      maxHeight: ITEM_HEIGHT * 4.5 + ITEM_PADDING_TOP,
      width: 250,
    },
  },
};

export default function Administration({ admins, availablePermissions, availableLanguages }) {
  const { admin_app_url, auth } = usePage().props;
  const theme = useTheme();
  const [openDialog, setOpenDialog] = useState(false);
  const [editingAdmin, setEditingAdmin] = useState(null);
  const [openSnackbar, setOpenSnackbar] = useState(false);
  const [adminMenuAnchorEl, setAdminMenuAnchorEl] = useState(null);
  const [adminMenuRow, setAdminMenuRow] = useState(null);

  const permissionLabelByName = React.useMemo(() => {
    return Object.fromEntries((availablePermissions || []).map((p) => [p.name, p.display_name]));
  }, [availablePermissions]);

  const { data, setData, post, patch, delete: destroy, processing, errors, reset, clearErrors } = useForm({
    name: '',
    email: '',
    password: '',
    access: [],
    major_scope: [],
  });

  const handleOpenDialog = (admin = null) => {
    clearErrors();
    if (admin) {
      setEditingAdmin(admin);
      setData({
        name: admin.name,
        email: admin.email,
        password: '',
        access: admin.access || [],
        major_scope: admin.major_scope || [],
      });
    } else {
      setEditingAdmin(null);
      reset();
    }
    setOpenDialog(true);
  };

  const handleCloseDialog = () => {
    setOpenDialog(false);
    setEditingAdmin(null);
    reset();
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (editingAdmin) {
      patch(`${admin_app_url}/administration/${editingAdmin.id}`, {
        onSuccess: () => {
          handleCloseDialog();
          setOpenSnackbar(true);
        },
      });
    } else {
      post(`${admin_app_url}/administration`, {
        onSuccess: () => {
          handleCloseDialog();
          setOpenSnackbar(true);
        },
      });
    }
  };

  const handleDelete = (admin) => {
    if (confirm(`Are you sure you want to delete ${admin.name}?`)) {
      destroy(`${admin_app_url}/administration/${admin.id}`, {
        onSuccess: () => setOpenSnackbar(true),
      });
    }
  };

  const openAdminMenu = (event, admin) => {
    setAdminMenuAnchorEl(event.currentTarget);
    setAdminMenuRow(admin);
  };

  const closeAdminMenu = () => {
    setAdminMenuAnchorEl(null);
    setAdminMenuRow(null);
  };

  const isRoot = (admin) => admin.access?.includes('*');

  return (
    <Box>
      <Head title="Administration" />

      <Stack spacing={3}>
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              Admin Management
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Manage system administrators and their access permissions.
            </Typography>
          </Box>
          <Button
            variant="contained"
            startIcon={<AddIcon />}
            onClick={() => handleOpenDialog()}
            sx={{ fontWeight: 600 }}
          >
            Add New Admin
          </Button>
        </Box>

        <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
          <Table sx={{ minWidth: 650 }} size="small">
            <TableHead>
              <TableRow sx={{ bgcolor: theme.palette.mode === 'dark' ? 'rgba(255, 255, 255, 0.03)' : 'grey.50' }}>
                <TableCell sx={{ fontWeight: 700, py: 1.25, fontSize: '0.75rem', textTransform: 'uppercase', color: 'text.secondary' }}>Admin</TableCell>
                <TableCell sx={{ fontWeight: 700, py: 1.25, fontSize: '0.75rem', textTransform: 'uppercase', color: 'text.secondary' }}>Email</TableCell>
                <TableCell sx={{ fontWeight: 700, py: 1.25, fontSize: '0.75rem', textTransform: 'uppercase', color: 'text.secondary' }}>Permissions</TableCell>
                <TableCell sx={{ fontWeight: 700, py: 1.25, fontSize: '0.75rem', textTransform: 'uppercase', color: 'text.secondary' }}>Language Scopes</TableCell>
                <TableCell align="right" sx={{ fontWeight: 700, py: 1.25, fontSize: '0.75rem', textTransform: 'uppercase', color: 'text.secondary' }}>Actions</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {admins.map((admin) => (
                <TableRow key={admin.id} hover>
                  <TableCell>
                    <Stack direction="row" spacing={1.5} alignItems="center">
                      <Avatar src={admin.image_url} sx={{ width: 32, height: 32 }}>
                        {admin.name.charAt(0)}
                      </Avatar>
                      <Typography variant="body2" sx={{ fontWeight: 500 }}>
                        {admin.name}
                        {admin.id === auth.admin.id && (
                          <Chip label="You" size="small" variant="outlined" sx={{ ml: 1, height: 20, fontSize: '0.625rem' }} />
                        )}
                      </Typography>
                    </Stack>
                  </TableCell>
                  <TableCell>
                    <Typography variant="body2" color="text.secondary">
                      {admin.email}
                    </Typography>
                  </TableCell>
                  <TableCell>
                    <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 0.5 }}>
                      {isRoot(admin) ? (
                        <Chip
                          icon={<ShieldIcon sx={{ fontSize: '0.875rem !important' }} />}
                          label="Root Access"
                          color="primary"
                          size="small"
                          sx={{ fontWeight: 600 }}
                        />
                      ) : (
                        admin.access?.map((perm) => (
                          <Chip key={perm} label={permissionLabelByName[perm] || perm} size="small" variant="outlined" />
                        ))
                      )}
                      {(!admin.access || admin.access.length === 0) && (
                        <Typography variant="caption" color="text.disabled">No permissions</Typography>
                      )}
                    </Box>
                  </TableCell>
                  <TableCell>
                    <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 0.5 }}>
                      {admin.major_scope?.includes('*') ? (
                        <Chip
                          icon={<LanguageIcon sx={{ fontSize: '0.875rem !important' }} />}
                          label="Global Scope"
                          color="secondary"
                          size="small"
                          sx={{ fontWeight: 600 }}
                        />
                      ) : (
                        admin.major_scope?.map((scope) => (
                          <Chip key={scope} label={scope} size="small" variant="outlined" />
                        ))
                      )}
                      {(!admin.major_scope || admin.major_scope.length === 0) && (
                        <Typography variant="caption" color="text.disabled">No scopes</Typography>
                      )}
                    </Box>
                  </TableCell>
                  <TableCell align="right">
                    <Tooltip title="Actions">
                      <IconButton size="small" onClick={(e) => openAdminMenu(e, admin)}>
                        <MoreVertIcon fontSize="small" />
                      </IconButton>
                    </Tooltip>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </TableContainer>
      </Stack>

      <Menu
        anchorEl={adminMenuAnchorEl}
        open={Boolean(adminMenuAnchorEl)}
        onClose={closeAdminMenu}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
        transformOrigin={{ vertical: 'top', horizontal: 'right' }}
      >
        <MenuItem
          onClick={() => {
            handleOpenDialog(adminMenuRow);
            closeAdminMenu();
          }}
        >
          Edit
        </MenuItem>
        <MenuItem
          disabled={adminMenuRow?.id === auth.admin.id}
          sx={{ color: 'error.main' }}
          onClick={() => {
            handleDelete(adminMenuRow);
            closeAdminMenu();
          }}
        >
          Delete
        </MenuItem>
      </Menu>

      {/* Add/Edit Dialog */}
      <Dialog open={openDialog} onClose={handleCloseDialog} maxWidth="sm" fullWidth>
        <DialogTitle sx={{ fontWeight: 700 }}>
          {editingAdmin ? `Edit Admin: ${editingAdmin.name}` : 'Create New Administrator'}
        </DialogTitle>
        <Box component="form" onSubmit={handleSubmit}>
          <DialogContent dividers>
            <Stack spacing={3}>
              <Grid container spacing={2}>
                <Grid item xs={12}>
                  <TextField
                    fullWidth
                    label="Full Name"
                    size="small"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    error={!!errors.name}
                    helperText={errors.name}
                    required
                  />
                </Grid>
                <Grid item xs={12}>
                  <TextField
                    fullWidth
                    label="Email Address"
                    type="email"
                    size="small"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    error={!!errors.email}
                    helperText={errors.email}
                    disabled={!!editingAdmin}
                    required
                  />
                </Grid>
                <Grid item xs={12}>
                  <TextField
                    fullWidth
                    label={editingAdmin ? "New Password (leave blank to keep current)" : "Password"}
                    type="password"
                    size="small"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    error={!!errors.password}
                    helperText={errors.password}
                    required={!editingAdmin}
                  />
                </Grid>
              </Grid>

              <Divider>
                <Chip label="Permissions & Scopes" size="small" variant="outlined" />
              </Divider>

              <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2}>
                <FormControl fullWidth size="small" sx={{ flex: 1 }}>
                  <InputLabel id="access-label">Sectors (Access)</InputLabel>
                  <Select
                    labelId="access-label"
                    multiple
                    value={data.access}
                    onChange={(e) => setData('access', e.target.value)}
                    input={<OutlinedInput label="Sectors (Access)" />}
                    renderValue={(selected) => (
                      <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 0.5 }}>
                        {selected.includes('*') ? (
                          <Chip key="*" label="ALL SECTORS" size="small" color="primary" />
                        ) : (
                          selected.map((value) => (
                            <Chip key={value} label={permissionLabelByName[value] || value} size="small" />
                          ))
                        )}
                      </Box>
                    )}
                    MenuProps={MenuProps}
                  >
                    <MenuItem value="*">
                      <Checkbox checked={data.access.indexOf('*') > -1} />
                      <ListItemText primary="ALL SECTORS (*)" primaryTypographyProps={{ fontWeight: 700 }} />
                    </MenuItem>
                    {availablePermissions.map((perm) => (
                      <MenuItem key={perm.id} value={perm.name}>
                        <Checkbox checked={data.access.indexOf(perm.name) > -1} />
                        <ListItemText primary={perm.display_name} />
                      </MenuItem>
                    ))}
                  </Select>
                </FormControl>

                <FormControl fullWidth size="small" sx={{ flex: 1 }}>
                  <InputLabel id="major-scope-label">Language Scopes</InputLabel>
                  <Select
                    labelId="major-scope-label"
                    multiple
                    value={data.major_scope}
                    onChange={(e) => setData('major_scope', e.target.value)}
                    input={<OutlinedInput label="Language Scopes" />}
                    renderValue={(selected) => (
                      <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 0.5 }}>
                        {selected.includes('*') ? (
                          <Chip key="*" label="GLOBAL SCOPE" size="small" color="secondary" />
                        ) : (
                          selected.map((value) => (
                            <Chip key={value} label={value} size="small" />
                          ))
                        )}
                      </Box>
                    )}
                    MenuProps={MenuProps}
                  >
                    <MenuItem value="*">
                      <Checkbox checked={data.major_scope.indexOf('*') > -1} />
                      <ListItemText primary="GLOBAL SCOPE (*)" primaryTypographyProps={{ fontWeight: 700 }} />
                    </MenuItem>
                    {availableLanguages.map((lang) => (
                      <MenuItem key={lang.id} value={lang.code}>
                        <Checkbox checked={data.major_scope.indexOf(lang.code) > -1} />
                        <ListItemText primary={lang.display_name} />
                      </MenuItem>
                    ))}
                  </Select>
                </FormControl>
              </Stack>
            </Stack>
          </DialogContent>
          <DialogActions sx={{ px: 3, py: 2 }}>
            <Button onClick={handleCloseDialog} color="inherit">Cancel</Button>
            <Button
              type="submit"
              variant="contained"
              disabled={processing}
              sx={{ fontWeight: 600 }}
            >
              {processing ? 'Saving...' : (editingAdmin ? 'Update Administrator' : 'Create Administrator')}
            </Button>
          </DialogActions>
        </Box>
      </Dialog>

      <Snackbar
        open={openSnackbar}
        autoHideDuration={4000}
        onClose={() => setOpenSnackbar(false)}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
      >
        <Alert severity="success" sx={{ width: '100%', borderRadius: 1.5 }}>
          Operation completed successfully!
        </Alert>
      </Snackbar>
    </Box>
  );
}

Administration.layout = (page) => <AdminLayout children={page} />;
