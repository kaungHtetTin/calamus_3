import React, { useState } from 'react';
import { Head, Link, useForm, usePage, router } from '@inertiajs/react';
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
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  TextField,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Avatar,
  Tooltip,
  Alert,
  Snackbar,
  Grid,
  Pagination,
  InputAdornment,
  useTheme,
} from '@mui/material';
import {
  Add as AddIcon,
  Search as SearchIcon,
  FilterList as FilterIcon,
  Person as PersonIcon,
  Verified as VerifiedIcon,
  Public as RegionIcon,
  ArrowUpward as SortAscIcon,
  ArrowDownward as SortDescIcon,
  Clear as ClearIcon,
  OpenInNew as OpenInNewIcon,
} from '@mui/icons-material';

export default function Users({ users, filters, regions }) {
  const { admin_app_url } = usePage().props;
  const theme = useTheme();
  const [openDialog, setOpenDialog] = useState(false);
  const [editingUser, setEditingUser] = useState(null);
  const [openSnackbar, setOpenSnackbar] = useState(false);
  const [search, setSearch] = useState(filters.search || '');
  const [showFilters, setShowFilters] = useState(false);

  const { data, setData, post, patch, delete: destroy, processing, errors, reset, clearErrors } = useForm({
    learner_name: '',
    learner_email: '',
    learner_phone: '',
    password: '',
    gender: '',
  });

  const handleOpenDialog = (user = null) => {
    clearErrors();
    if (user) {
      setEditingUser(user);
      setData({
        learner_name: user.learner_name,
        learner_email: user.learner_email,
        learner_phone: user.learner_phone || '',
        password: '',
        gender: user.gender || '',
      });
    } else {
      setEditingUser(null);
      reset();
    }
    setOpenDialog(true);
  };

  const handleCloseDialog = () => {
    setOpenDialog(false);
    setEditingUser(null);
    reset();
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (editingUser) {
      patch(`${admin_app_url}/users/${editingUser.user_id}`, {
        onSuccess: () => {
          handleCloseDialog();
          setOpenSnackbar(true);
        },
      });
    } else {
      post(`${admin_app_url}/users`, {
        onSuccess: () => {
          handleCloseDialog();
          setOpenSnackbar(true);
        },
      });
    }
  };

  const handleSearch = (e) => {
    if (e) e.preventDefault();
    router.get(`${admin_app_url}/users`, { ...filters, search, page: 1 }, { preserveState: true });
  };

  const handlePageChange = (event, value) => {
    router.get(`${admin_app_url}/users`, { ...filters, page: value }, { preserveState: true });
  };

  const handleFilterChange = (key, value) => {
    const newFilters = { ...filters, [key]: value, page: 1 };
    if (!value) delete newFilters[key];
    router.get(`${admin_app_url}/users`, newFilters, { preserveState: true });
  };

  const handleSort = (field) => {
    const order = filters.sort === field && filters.order === 'asc' ? 'desc' : 'asc';
    router.get(`${admin_app_url}/users`, { ...filters, sort: field, order, page: 1 }, { preserveState: true });
  };

  const clearFilters = () => {
    setSearch('');
    router.get(`${admin_app_url}/users`, {}, { preserveState: true });
  };

  const hasActiveFilters = Object.keys(filters).some(key => ['gender', 'verified', 'region', 'search'].includes(key));

  return (
    <Box>
      <Head title="User Management" />

      <Stack spacing={2.5}>
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              User Management
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Manage learners, their profiles, and account settings.
            </Typography>
          </Box>
          <Button
            variant="contained"
            startIcon={<AddIcon />}
            onClick={() => handleOpenDialog()}
            sx={{ fontWeight: 600, borderRadius: 2 }}
          >
            Add New User
          </Button>
        </Box>

        <Paper variant="outlined" sx={{ p: 2, borderRadius: 3, bgcolor: theme.palette.mode === 'dark' ? 'background.paper' : 'grey.50' }}>
          <Stack spacing={2}>
            <Grid container spacing={2} alignItems="center">
              <Grid item xs={12} md={5}>
                <Box component="form" onSubmit={handleSearch}>
                  <TextField
                    fullWidth
                    size="small"
                    placeholder="Search by name, email, or phone..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    InputProps={{
                      startAdornment: (
                        <InputAdornment position="start">
                          <SearchIcon fontSize="small" color="action" />
                        </InputAdornment>
                      ),
                      endAdornment: search && (
                        <InputAdornment position="end">
                          <IconButton size="small" onClick={() => { setSearch(''); handleSearch(); }}>
                            <ClearIcon fontSize="inherit" />
                          </IconButton>
                        </InputAdornment>
                      ),
                      sx: { borderRadius: 2, bgcolor: theme.palette.mode === 'dark' ? 'rgba(255,255,255,0.05)' : 'white' }
                    }}
                  />
                </Box>
              </Grid>
              <Grid item xs={12} md={7}>
                <Stack direction="row" spacing={1} justifyContent={{ xs: 'flex-start', md: 'flex-end' }}>
                  <Button
                    variant={showFilters ? "contained" : "outlined"}
                    color={hasActiveFilters ? "primary" : "inherit"}
                    startIcon={<FilterIcon />}
                    size="small"
                    onClick={() => setShowFilters(!showFilters)}
                    sx={{ borderRadius: 2, px: 2 }}
                  >
                    Filters {hasActiveFilters && `(Active)`}
                  </Button>
                  {hasActiveFilters && (
                    <Button
                      variant="text"
                      color="error"
                      size="small"
                      onClick={clearFilters}
                      sx={{ borderRadius: 2 }}
                    >
                      Reset
                    </Button>
                  )}
                </Stack>
              </Grid>
            </Grid>

            {showFilters && (
              <Grid container spacing={2} sx={{ pt: 1 }}>
                <Grid item xs={12} sm={4} md={3}>
                  <FormControl fullWidth size="small" sx={{ minWidth: 150 }}>
                    <InputLabel id="gender-filter-label">Gender</InputLabel>
                    <Select
                      labelId="gender-filter-label"
                      value={filters.gender || ''}
                      label="Gender"
                      onChange={(e) => handleFilterChange('gender', e.target.value)}
                      sx={{ borderRadius: 2, bgcolor: theme.palette.mode === 'dark' ? 'rgba(255,255,255,0.05)' : 'white' }}
                    >
                      <MenuItem value="">All Genders</MenuItem>
                      <MenuItem value="male">Male</MenuItem>
                      <MenuItem value="female">Female</MenuItem>
                      <MenuItem value="other">Other</MenuItem>
                    </Select>
                  </FormControl>
                </Grid>
                <Grid item xs={12} sm={4} md={3}>
                  <FormControl fullWidth size="small" sx={{ minWidth: 150 }}>
                    <InputLabel id="verified-filter-label">Verification</InputLabel>
                    <Select
                      labelId="verified-filter-label"
                      value={filters.verified || ''}
                      label="Verification"
                      onChange={(e) => handleFilterChange('verified', e.target.value)}
                      sx={{ borderRadius: 2, bgcolor: theme.palette.mode === 'dark' ? 'rgba(255,255,255,0.05)' : 'white' }}
                      startAdornment={<VerifiedIcon fontSize="small" sx={{ mr: 1, color: 'action.active' }} />}
                    >
                      <MenuItem value="">All Status</MenuItem>
                      <MenuItem value="yes">Verified</MenuItem>
                      <MenuItem value="no">Unverified</MenuItem>
                    </Select>
                  </FormControl>
                </Grid>
                <Grid item xs={12} sm={4} md={3}>
                  <FormControl fullWidth size="small" sx={{ minWidth: 150 }}>
                    <InputLabel id="region-filter-label">Region</InputLabel>
                    <Select
                      labelId="region-filter-label"
                      value={filters.region || ''}
                      label="Region"
                      onChange={(e) => handleFilterChange('region', e.target.value)}
                      sx={{ borderRadius: 2, bgcolor: theme.palette.mode === 'dark' ? 'rgba(255,255,255,0.05)' : 'white' }}
                      startAdornment={<RegionIcon fontSize="small" sx={{ mr: 1, color: 'action.active' }} />}
                    >
                      <MenuItem value="">All Regions</MenuItem>
                      {regions && regions.map(region => (
                        <MenuItem key={region} value={region}>{region}</MenuItem>
                      ))}
                    </Select>
                  </FormControl>
                </Grid>
              </Grid>
            )}
          </Stack>
        </Paper>

        <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 3, overflow: 'hidden' }}>
          <Table sx={{ minWidth: 650 }} size="small">
            <TableHead>
              <TableRow sx={{ bgcolor: theme.palette.mode === 'dark' ? 'rgba(255, 255, 255, 0.03)' : 'grey.50' }}>
                <TableCell 
                  sx={{ fontWeight: 700, py: 1.5, fontSize: '0.75rem', textTransform: 'uppercase', color: 'text.secondary', cursor: 'pointer' }}
                  onClick={() => handleSort('learner_name')}
                >
                  <Stack direction="row" spacing={0.5} alignItems="center">
                    <span>Learner</span>
                    {filters.sort === 'learner_name' ? (
                      filters.order === 'asc' ? <SortAscIcon sx={{ fontSize: 14 }} /> : <SortDescIcon sx={{ fontSize: 14 }} />
                    ) : null}
                  </Stack>
                </TableCell>
                <TableCell 
                  sx={{ fontWeight: 700, py: 1.5, fontSize: '0.75rem', textTransform: 'uppercase', color: 'text.secondary', cursor: 'pointer' }}
                  onClick={() => handleSort('learner_email')}
                >
                  <Stack direction="row" spacing={0.5} alignItems="center">
                    <span>Email</span>
                    {filters.sort === 'learner_email' ? (
                      filters.order === 'asc' ? <SortAscIcon sx={{ fontSize: 14 }} /> : <SortDescIcon sx={{ fontSize: 14 }} />
                    ) : null}
                  </Stack>
                </TableCell>
                <TableCell sx={{ fontWeight: 700, py: 1.5, fontSize: '0.75rem', textTransform: 'uppercase', color: 'text.secondary' }}>Phone</TableCell>
                <TableCell sx={{ fontWeight: 700, py: 1.5, fontSize: '0.75rem', textTransform: 'uppercase', color: 'text.secondary' }}>Status</TableCell>
                <TableCell align="right" sx={{ fontWeight: 700, py: 1.5, fontSize: '0.75rem', textTransform: 'uppercase', color: 'text.secondary' }}>Actions</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {users.data.map((user) => (
                <TableRow key={user.user_id} hover sx={{ '&:last-child td, &:last-child th': { border: 0 } }}>
                  <TableCell>
                    <Stack direction="row" spacing={1.5} alignItems="center">
                      <Avatar 
                        src={user.learner_image ? `${admin_app_url.replace('/admin', '')}/uploads/admin/images/${user.learner_image}` : null} 
                        sx={{ width: 36, height: 36, border: '1px solid', borderColor: 'divider' }}
                      >
                        {user.learner_name.charAt(0)}
                      </Avatar>
                      <Box>
                        <Typography variant="body2" sx={{ fontWeight: 600 }}>
                          {user.learner_name}
                        </Typography>
                        <Typography variant="caption" color="text.secondary">
                          ID: {user.user_id} • {user.gender || 'Not set'}
                        </Typography>
                      </Box>
                    </Stack>
                  </TableCell>
                  <TableCell>
                    <Typography variant="body2" color="text.secondary">
                      {user.learner_email}
                    </Typography>
                  </TableCell>
                  <TableCell>
                    <Typography variant="body2" color="text.secondary">
                      {user.learner_phone || '-'}
                    </Typography>
                  </TableCell>
                  <TableCell>
                    {user.email_verified_at ? (
                      <Tooltip title={`Verified at ${new Date(user.email_verified_at).toLocaleDateString()}`}>
                        <Box sx={{ display: 'inline-flex', alignItems: 'center', color: 'success.main', bgcolor: 'success.lighter', px: 1, py: 0.25, borderRadius: 1 }}>
                          <VerifiedIcon sx={{ fontSize: 14, mr: 0.5 }} />
                          <Typography variant="caption" sx={{ fontWeight: 600 }}>Verified</Typography>
                        </Box>
                      </Tooltip>
                    ) : (
                      <Box sx={{ display: 'inline-flex', alignItems: 'center', color: 'warning.main', bgcolor: 'warning.lighter', px: 1, py: 0.25, borderRadius: 1 }}>
                        <Typography variant="caption" sx={{ fontWeight: 600 }}>Unverified</Typography>
                      </Box>
                    )}
                  </TableCell>
                  <TableCell align="right">
                    <Stack direction="row" spacing={0.5} justifyContent="flex-end">
                      <Tooltip title="Manage User">
                        <IconButton size="small" component={Link} href={`${admin_app_url}/users/${user.user_id}/edit`} sx={{ color: 'text.secondary' }}>
                          <OpenInNewIcon fontSize="small" />
                        </IconButton>
                      </Tooltip>
                    </Stack>
                  </TableCell>
                </TableRow>
              ))}
              {users.data.length === 0 && (
                <TableRow>
                  <TableCell colSpan={5} align="center" sx={{ py: 8 }}>
                    <Stack spacing={1} alignItems="center">
                      <Box sx={{ p: 2, bgcolor: 'action.hover', borderRadius: '50%' }}>
                        <SearchIcon sx={{ fontSize: 40, color: 'text.disabled' }} />
                      </Box>
                      <Typography variant="subtitle2" color="text.secondary">No users found matching your criteria</Typography>
                      <Button variant="text" size="small" onClick={clearFilters}>Clear all filters</Button>
                    </Stack>
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </TableContainer>

        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', px: 1 }}>
          <Typography variant="caption" color="text.secondary">
            Showing {users.from || 0} to {users.to || 0} of {users.total} users
          </Typography>
          <Pagination
            count={users.last_page}
            page={users.current_page}
            onChange={handlePageChange}
            color="primary"
            size="small"
            sx={{
              '& .MuiPaginationItem-root': { borderRadius: 1.5 }
            }}
          />
        </Box>
      </Stack>

      {/* Add/Edit Dialog */}
      <Dialog open={openDialog} onClose={handleCloseDialog} maxWidth="sm" fullWidth>
        <DialogTitle sx={{ fontWeight: 700 }}>
          {editingUser ? `Edit User: ${editingUser.learner_name}` : 'Create New User'}
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
                    value={data.learner_name}
                    onChange={(e) => setData('learner_name', e.target.value)}
                    error={!!errors.learner_name}
                    helperText={errors.learner_name}
                    required
                  />
                </Grid>
                <Grid item xs={12} sm={6}>
                  <TextField
                    fullWidth
                    label="Email Address"
                    type="email"
                    size="small"
                    value={data.learner_email}
                    onChange={(e) => setData('learner_email', e.target.value)}
                    error={!!errors.learner_email}
                    helperText={errors.learner_email}
                    autoComplete="off"
                    required
                  />
                </Grid>
                <Grid item xs={12} sm={6}>
                  <TextField
                    fullWidth
                    label="Phone Number"
                    size="small"
                    value={data.learner_phone}
                    onChange={(e) => setData('learner_phone', e.target.value)}
                    error={!!errors.learner_phone}
                    helperText={errors.learner_phone}
                  />
                </Grid>
                <Grid item xs={12} sm={6}>
                  <FormControl fullWidth size="small" sx={{ minWidth: 160 }}>
                    <InputLabel id="gender-label">Gender</InputLabel>
                    <Select
                      labelId="gender-label"
                      value={data.gender}
                      label="Gender"
                      onChange={(e) => setData('gender', e.target.value)}
                    >
                      <MenuItem value="male">Male</MenuItem>
                      <MenuItem value="female">Female</MenuItem>
                      <MenuItem value="other">Other</MenuItem>
                    </Select>
                  </FormControl>
                </Grid>
                <Grid item xs={12} sm={6}>
                  <TextField
                    fullWidth
                    label={editingUser ? "New Password (leave blank to keep current)" : "Password"}
                    type="password"
                    size="small"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    error={!!errors.password}
                    helperText={errors.password}
                    autoComplete="new-password"
                    required={!editingUser}
                  />
                </Grid>
              </Grid>
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
              {processing ? 'Saving...' : (editingUser ? 'Update User' : 'Create User')}
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

Users.layout = (page) => <AdminLayout children={page} />;
