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
  FormControl,
  InputLabel,
  IconButton,
  ListItemIcon,
  ListItemText,
  Menu,
  MenuItem,
  OutlinedInput,
  Paper,
  Select,
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
import { Add as AddIcon, Edit as EditIcon, Delete as DeleteIcon, MoreVert as MoreVertIcon, Language as LanguageIcon } from '@mui/icons-material';

export default function PackagePlans({ packagePlans, languageOptions, courseOptions }) {
  const { admin_app_url } = usePage().props;
  const rows = Array.isArray(packagePlans) ? packagePlans : [];
  const languages = Array.isArray(languageOptions) ? languageOptions : [];
  const courses = Array.isArray(courseOptions) ? courseOptions : [];
  const appBaseUrl = admin_app_url.replace(/\/admin\/?$/, '');

  const [openDialog, setOpenDialog] = React.useState(false);
  const [editing, setEditing] = React.useState(null);
  const [rowMenu, setRowMenu] = React.useState({ anchorEl: null, row: null });
  const rowMenuOpen = Boolean(rowMenu.anchorEl);
  const [filterMajor, setFilterMajor] = React.useState('all');
  const [filterStatus, setFilterStatus] = React.useState('all');
  const [search, setSearch] = React.useState('');

  const buildImageUrl = (path) => {
    if (!path) {
      return '';
    }
    if (String(path).startsWith('http://') || String(path).startsWith('https://')) {
      return path;
    }
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

  const filteredRows = React.useMemo(() => {
    const q = String(search || '').toLowerCase().trim();
    return rows.filter((row) => {
      const major = String(row.major || '').toLowerCase().trim();
      if (filterMajor !== 'all' && major !== String(filterMajor).toLowerCase().trim()) {
        return false;
      }

      if (filterStatus === 'active' && !row.active) {
        return false;
      }
      if (filterStatus === 'inactive' && row.active) {
        return false;
      }

      if (q) {
        const name = String(row.name || '').toLowerCase();
        const desc = String(row.description || '').toLowerCase();
        return name.includes(q) || desc.includes(q);
      }

      return true;
    });
  }, [rows, filterMajor, filterStatus, search]);

  const coursesByMajor = React.useMemo(() => {
    const map = new Map();
    courses.forEach((c) => {
      const major = String(c.major || '').toLowerCase().trim();
      if (!major) return;
      if (!map.has(major)) map.set(major, []);
      map.get(major).push(c);
    });
    for (const [k, arr] of map.entries()) {
      arr.sort((a, b) => String(a.title || '').localeCompare(String(b.title || '')));
      map.set(k, arr);
    }
    return map;
  }, [courses]);

  const courseTitleById = React.useMemo(() => {
    const map = new Map();
    courses.forEach((c) => map.set(Number(c.id), String(c.title || `Course #${c.id}`)));
    return map;
  }, [courses]);

  const { data, setData, post, patch, processing, errors, reset, clearErrors } = useForm({
    major: '',
    name: '',
    description: '',
    price: 0,
    active: true,
    sort_order: 0,
    course_ids: [],
  });

  const handleOpenAdd = () => {
    setEditing(null);
    reset();
    clearErrors();
    setData({
      major: languages[0]?.code || '',
      name: '',
      description: '',
      price: 0,
      active: true,
      sort_order: 0,
      course_ids: [],
    });
    setOpenDialog(true);
  };

  const handleOpenEdit = (row) => {
    setEditing(row);
    clearErrors();
    setData({
      major: row.major || '',
      name: row.name || '',
      description: row.description || '',
      price: Number(row.price ?? 0),
      active: Boolean(row.active),
      sort_order: Number(row.sort_order ?? 0),
      course_ids: Array.isArray(row.course_ids) ? row.course_ids : [],
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
      patch(`${admin_app_url}/package-plans/${editing.id}`, { preserveScroll: true, onSuccess: handleClose });
      return;
    }
    post(`${admin_app_url}/package-plans`, { preserveScroll: true, onSuccess: handleClose });
  };

  const handleDelete = (row) => {
    if (!confirm(`Delete package plan "${row.name}"?`)) return;
    router.delete(`${admin_app_url}/package-plans/${row.id}`, { preserveScroll: true });
  };

  const openRowMenu = (event, row) => {
    setRowMenu({ anchorEl: event.currentTarget, row });
  };

  const closeRowMenu = () => {
    setRowMenu({ anchorEl: null, row: null });
  };

  const majorCourses = coursesByMajor.get(String(data.major || '').toLowerCase().trim()) || [];

  return (
    <Box>
      <Head title="Package Plans" />

      <Stack spacing={1.5}>
        <Stack direction="row" justifyContent="space-between" alignItems="center">
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              Package Plans
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Create and manage bundle plans and their included courses.
            </Typography>
          </Box>
          <Button variant="contained" size="small" startIcon={<AddIcon />} onClick={handleOpenAdd}>
            Add Plan
          </Button>
        </Stack>

        <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
          <Stack
            direction={{ xs: 'column', md: 'row' }}
            spacing={1.25}
            alignItems={{ xs: 'stretch', md: 'center' }}
            justifyContent="space-between"
          >
            <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
              Filters
            </Typography>
            <Stack direction={{ xs: 'column', sm: 'row' }} spacing={1} sx={{ width: { xs: '100%', md: 'auto' } }}>
              <TextField
                select
                size="small"
                label="Major"
                value={filterMajor}
                onChange={(e) => setFilterMajor(e.target.value)}
                sx={{ minWidth: 180 }}
              >
                <MenuItem value="all">All</MenuItem>
                {languages.map((l) => (
                  <MenuItem key={`pp-filter-major-${l.code}`} value={l.code}>
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
                size="small"
                label="Status"
                value={filterStatus}
                onChange={(e) => setFilterStatus(e.target.value)}
                sx={{ minWidth: 160 }}
              >
                <MenuItem value="all">All</MenuItem>
                <MenuItem value="active">Active</MenuItem>
                <MenuItem value="inactive">Inactive</MenuItem>
              </TextField>
              <TextField
                size="small"
                label="Search"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                sx={{ minWidth: 260 }}
              />
            </Stack>
          </Stack>
        </Paper>

        <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
          <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
            <Table size="small" sx={{ tableLayout: 'auto', '& .MuiTableCell-root': { px: 1, py: 0.75, verticalAlign: 'top' } }}>
              <TableHead>
                <TableRow>
                  <TableCell sx={{ fontWeight: 700, width: 200 }}>Major</TableCell>
                  <TableCell sx={{ fontWeight: 700, minWidth: 220 }}>Plan</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 140 }}>Price</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 110 }}>Active</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 90 }}>Sort</TableCell>
                  <TableCell sx={{ fontWeight: 700, minWidth: 260 }}>Courses</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 110 }}>Actions</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {filteredRows.map((row) => {
                  const lang = languageByCode.get(String(row.major || '').toLowerCase().trim());
                  const courseIds = Array.isArray(row.course_ids) ? row.course_ids : [];
                  const courseTitles = courseIds.map((id) => courseTitleById.get(Number(id)) || `Course #${id}`);
                  return (
                    <TableRow key={`pp-${row.id}`} hover>
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
                          {row.name}
                        </Typography>
                        {row.description ? (
                          <Typography variant="caption" color="text.secondary">
                            {row.description}
                          </Typography>
                        ) : null}
                      </TableCell>
                      <TableCell>
                        <Typography variant="body2">{Number(row.price ?? 0).toLocaleString()}</Typography>
                      </TableCell>
                      <TableCell>
                        <Chip size="small" label={row.active ? 'Active' : 'Inactive'} color={row.active ? 'success' : 'default'} />
                      </TableCell>
                      <TableCell>
                        <Typography variant="body2">{row.sort_order ?? 0}</Typography>
                      </TableCell>
                      <TableCell>
                        <Stack direction="row" spacing={0.75} useFlexGap flexWrap="wrap">
                          {courseTitles.map((title) => (
                            <Chip key={`ppt-${row.id}-${title}`} size="small" label={title} variant="outlined" />
                          ))}
                          {courseTitles.length === 0 ? (
                            <Typography variant="body2" color="text.secondary">
                              -
                            </Typography>
                          ) : null}
                        </Stack>
                      </TableCell>
                      <TableCell>
                        <IconButton size="small" aria-label="actions" onClick={(e) => openRowMenu(e, row)}>
                          <MoreVertIcon fontSize="small" />
                        </IconButton>
                      </TableCell>
                    </TableRow>
                  );
                })}
                {filteredRows.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={7}>
                      <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                        No package plans found.
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
            {(() => {
              const lang = languageByCode.get(String(data.major || '').toLowerCase().trim());
              if (!lang) {
                return (
                  <Stack direction="row" spacing={1} alignItems="center">
                    <Avatar sx={{ width: 28, height: 28, bgcolor: 'action.selected' }}>
                      <LanguageIcon fontSize="small" />
                    </Avatar>
                    <Box>
                      <Typography variant="subtitle1" sx={{ fontWeight: 700, lineHeight: 1.15 }}>
                        Select Major
                      </Typography>
                      <Typography variant="caption" color="text.secondary" sx={{ lineHeight: 1.1 }}>
                        -
                      </Typography>
                    </Box>
                  </Stack>
                );
              }
              return (
                <Stack direction="row" spacing={1} alignItems="center">
                  <Avatar
                    src={buildImageUrl(lang.image_path)}
                    sx={{ width: 28, height: 28, bgcolor: lang.primary_color || 'action.selected' }}
                  >
                    <LanguageIcon fontSize="small" />
                  </Avatar>
                  <Box>
                    <Typography variant="subtitle1" sx={{ fontWeight: 700, lineHeight: 1.15 }}>
                      {lang.name}
                    </Typography>
                    <Typography variant="caption" color="text.secondary" sx={{ lineHeight: 1.1 }}>
                      {lang.code}
                    </Typography>
                  </Box>
                </Stack>
              );
            })()}
            <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
              {editing ? 'Edit Plan' : 'Add Plan'}
            </Typography>
          </Stack>
        </DialogTitle>
        <Divider />
        <DialogContent sx={{ pt: 2 }}>
          <Stack spacing={1.25}>
            <Stack direction={{ xs: 'column', sm: 'row' }} spacing={1.25}>
              <TextField
                select
                label="Major"
                value={data.major}
                onChange={(e) => {
                  const next = e.target.value;
                  setData('major', next);
                  setData('course_ids', []);
                }}
                error={Boolean(errors.major)}
                helperText={errors.major}
                fullWidth
                size="small"
              >
                {languages.map((l) => (
                  <MenuItem key={`pp-major-${l.code}`} value={l.code}>
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
                label="Price"
                type="number"
                value={data.price}
                onChange={(e) => setData('price', Number(e.target.value))}
                error={Boolean(errors.price)}
                helperText={errors.price}
                fullWidth
                size="small"
              />
              <TextField
                label="Sort Order"
                type="number"
                value={data.sort_order}
                onChange={(e) => setData('sort_order', Number(e.target.value))}
                error={Boolean(errors.sort_order)}
                helperText={errors.sort_order}
                fullWidth
                size="small"
              />
            </Stack>

            <TextField
              label="Plan Name"
              value={data.name}
              onChange={(e) => setData('name', e.target.value)}
              error={Boolean(errors.name)}
              helperText={errors.name}
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

            <FormControlLabel
              control={<Checkbox checked={Boolean(data.active)} onChange={(e) => setData('active', e.target.checked)} />}
              label="Active"
            />

            <FormControl fullWidth size="small" error={Boolean(errors.course_ids)}>
              <InputLabel id="pp-courses-label">Included Courses</InputLabel>
              <Select
                labelId="pp-courses-label"
                multiple
                value={data.course_ids}
                onChange={(e) => setData('course_ids', e.target.value)}
                input={<OutlinedInput label="Included Courses" />}
                renderValue={(selected) => (
                  <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 0.5 }}>
                    {selected.map((id) => (
                      <Chip key={`pp-sel-${id}`} label={courseTitleById.get(Number(id)) || `Course #${id}`} size="small" />
                    ))}
                  </Box>
                )}
              >
                {majorCourses.map((c) => (
                  <MenuItem key={`pp-course-${c.id}`} value={c.id}>
                    <Checkbox checked={data.course_ids.indexOf(c.id) > -1} />
                    <ListItemText primary={c.title} secondary={Number(c.fee || 0) > 0 ? `${Number(c.fee).toLocaleString()} kyats` : 'Free'} />
                  </MenuItem>
                ))}
              </Select>
              {errors.course_ids ? (
                <Typography variant="caption" color="error.main" sx={{ mt: 0.5 }}>
                  {errors.course_ids}
                </Typography>
              ) : null}
            </FormControl>
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

PackagePlans.layout = (page) => <AdminLayout children={page} />;
