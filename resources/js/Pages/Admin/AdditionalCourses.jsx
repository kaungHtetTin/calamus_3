import React, { useMemo, useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import {
  Alert,
  Box,
  Button,
  Chip,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  IconButton,
  Menu,
  MenuItem,
  Paper,
  Snackbar,
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
  Delete as DeleteIcon,
  Edit as EditIcon,
  MoreVert as MoreVertIcon,
  OpenInNew as OpenInNewIcon,
} from '@mui/icons-material';

const defaultCourseForm = {
  title: '',
  sorting: 0,
  is_vip: false,
  active: true,
};

export default function AdditionalCourses({ courses = [] }) {
  const { admin_app_url, flash } = usePage().props;
  const [search, setSearch] = useState('');
  const [dialogOpen, setDialogOpen] = useState(false);
  const [editingCourse, setEditingCourse] = useState(null);
  const [menuAnchorEl, setMenuAnchorEl] = useState(null);
  const [menuTarget, setMenuTarget] = useState(null);

  const {
    data,
    setData,
    post,
    processing,
    errors,
    reset,
    clearErrors,
    transform,
    delete: destroy,
  } = useForm(defaultCourseForm);

  const filteredCourses = useMemo(() => {
    const keyword = search.trim().toLowerCase();
    if (!keyword) {
      return courses || [];
    }
    return (courses || []).filter((course) => {
      const title = String(course.title || '').toLowerCase();
      const idText = String(course.course_id || '');
      return title.includes(keyword) || idText.includes(keyword);
    });
  }, [courses, search]);

  const openCreate = () => {
    setEditingCourse(null);
    clearErrors();
    reset();
    setData({ ...defaultCourseForm });
    setDialogOpen(true);
  };

  const openEdit = (course) => {
    setEditingCourse(course);
    clearErrors();
    setData({
      title: String(course?.title || ''),
      sorting: Number(course?.sorting || 0),
      is_vip: Number(course?.is_vip || 0) === 1,
      active: Number(course?.active || 0) === 1,
    });
    setDialogOpen(true);
  };

  const closeDialog = () => {
    setDialogOpen(false);
    setEditingCourse(null);
    clearErrors();
    reset();
    transform((formData) => formData);
  };

  const submit = (event) => {
    event.preventDefault();

    const payload = {
      title: data.title,
      sorting: Number(data.sorting || 0),
      is_vip: Boolean(data.is_vip),
      active: Boolean(data.active),
    };

    if (editingCourse) {
      transform(() => ({ ...payload, _method: 'patch' }));
      post(`${admin_app_url}/additional-lessons/courses/${editingCourse.course_id}`, {
        onSuccess: () => closeDialog(),
        onFinish: () => transform((formData) => formData),
      });
      return;
    }

    post(`${admin_app_url}/additional-lessons/courses`, {
      data: payload,
      onSuccess: () => closeDialog(),
    });
  };

  const openMenu = (event, course) => {
    event.stopPropagation();
    setMenuAnchorEl(event.currentTarget);
    setMenuTarget(course);
  };

  const closeMenu = () => {
    setMenuAnchorEl(null);
    setMenuTarget(null);
  };

  const removeCourse = (course) => {
    if (!course) {
      return;
    }
    if (!confirm(`Delete additional course "${course.title}"? This also deletes its categories and lessons.`)) {
      return;
    }
    destroy(`${admin_app_url}/additional-lessons/courses/${course.course_id}`);
  };

  return (
    <Box>
      <Head title="Additional Courses" />
      <Stack spacing={2.5}>
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 1.5, flexWrap: 'wrap' }}>
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              Additional Courses
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Courses with major = not. Used by Additional Lessons workspace.
            </Typography>
          </Box>
          <Stack direction="row" spacing={1}>
            <Button component={Link} href={`${admin_app_url}/additional-lessons`} variant="outlined" size="small">
              Back to Additional Lessons
            </Button>
            <Button onClick={openCreate} variant="contained" startIcon={<AddIcon />}>
              Create Course
            </Button>
          </Stack>
        </Box>

        {Boolean(flash?.success) && <Alert severity="success">{flash.success}</Alert>}

        <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
          <Stack direction={{ xs: 'column', sm: 'row' }} spacing={1} alignItems={{ xs: 'stretch', sm: 'center' }} justifyContent="space-between" sx={{ mb: 1.5 }}>
            <TextField
              label="Search (title or ID)"
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              size="small"
              sx={{ width: { xs: '100%', sm: 320 } }}
            />
            <Typography variant="caption" color="text.secondary">
              {filteredCourses.length} courses
            </Typography>
          </Stack>

          <TableContainer>
            <Table size="small">
              <TableHead>
                <TableRow>
                  <TableCell sx={{ fontWeight: 700 }}>ID</TableCell>
                  <TableCell sx={{ fontWeight: 700 }}>Title</TableCell>
                  <TableCell sx={{ fontWeight: 700 }}>Flags</TableCell>
                  <TableCell sx={{ fontWeight: 700 }}>Sorting</TableCell>
                  <TableCell sx={{ fontWeight: 700 }}>Categories</TableCell>
                  <TableCell sx={{ fontWeight: 700 }}>Lessons</TableCell>
                  <TableCell align="right" sx={{ fontWeight: 700 }}>Actions</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {filteredCourses.map((course) => (
                  <TableRow
                    key={course.course_id}
                    hover
                    sx={{ cursor: 'pointer' }}
                    onClick={() => router.visit(`${admin_app_url}/additional-lessons/workspace?courseId=${encodeURIComponent(String(course.course_id))}`)}
                  >
                    <TableCell>{course.course_id}</TableCell>
                    <TableCell sx={{ maxWidth: 520 }}>
                      <Typography variant="body2" sx={{ fontWeight: 700 }} noWrap>
                        {course.title}
                      </Typography>
                    </TableCell>
                    <TableCell>
                      <Stack direction="row" spacing={0.75}>
                        <Chip size="small" label={Number(course.active) === 1 ? 'Active' : 'Inactive'} variant="outlined" />
                        {Number(course.is_vip) === 1 && <Chip size="small" label="VIP" color="warning" />}
                      </Stack>
                    </TableCell>
                    <TableCell>{Number(course.sorting || 0)}</TableCell>
                    <TableCell>{Number(course.categories_count || 0)}</TableCell>
                    <TableCell>{Number(course.lessons_count || 0)}</TableCell>
                    <TableCell align="right" sx={{ whiteSpace: 'nowrap' }}>
                      <IconButton
                        size="small"
                        title="Open workspace"
                        onClick={(event) => {
                          event.stopPropagation();
                          router.visit(`${admin_app_url}/additional-lessons/workspace?courseId=${encodeURIComponent(String(course.course_id))}`);
                        }}
                      >
                        <OpenInNewIcon fontSize="small" />
                      </IconButton>
                      <IconButton size="small" onClick={(event) => openMenu(event, course)} title="More">
                        <MoreVertIcon fontSize="small" />
                      </IconButton>
                    </TableCell>
                  </TableRow>
                ))}
                {!filteredCourses.length && (
                  <TableRow>
                    <TableCell colSpan={7}>
                      <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                        No additional courses found.
                      </Typography>
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </TableContainer>
        </Paper>
      </Stack>

      <Menu
        anchorEl={menuAnchorEl}
        open={Boolean(menuAnchorEl)}
        onClose={closeMenu}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
        transformOrigin={{ vertical: 'top', horizontal: 'right' }}
      >
        <MenuItem
          onClick={() => {
            const course = menuTarget;
            closeMenu();
            if (course) {
              openEdit(course);
            }
          }}
        >
          <EditIcon fontSize="small" />
          <Box sx={{ width: 8 }} />
          Edit
        </MenuItem>
        <MenuItem
          onClick={() => {
            const course = menuTarget;
            closeMenu();
            if (course) {
              removeCourse(course);
            }
          }}
        >
          <DeleteIcon fontSize="small" color="error" />
          <Box sx={{ width: 8 }} />
          Delete
        </MenuItem>
      </Menu>

      <Dialog open={dialogOpen} onClose={closeDialog} maxWidth="sm" fullWidth>
        <DialogTitle>{editingCourse ? 'Edit Additional Course' : 'Create Additional Course'}</DialogTitle>
        <Box component="form" onSubmit={submit}>
          <DialogContent dividers>
            {Boolean(errors?.title) && (
              <Alert severity="error" sx={{ mb: 1.25 }}>
                {errors.title}
              </Alert>
            )}
            <Stack spacing={1.5}>
              <TextField
                label="Title"
                value={data.title}
                onChange={(event) => setData('title', event.target.value)}
                error={Boolean(errors.title)}
                helperText={errors.title}
                autoFocus
              />
              <TextField
                type="number"
                label="Sorting"
                value={data.sorting}
                onChange={(event) => setData('sorting', event.target.value)}
                error={Boolean(errors.sorting)}
                helperText={errors.sorting}
              />
              <Stack direction={{ xs: 'column', sm: 'row' }} spacing={1.25}>
                <TextField
                  select
                  fullWidth
                  label="Active"
                  value={data.active ? '1' : '0'}
                  onChange={(event) => setData('active', event.target.value === '1')}
                  error={Boolean(errors.active)}
                  helperText={errors.active}
                >
                  <MenuItem value="1">Active</MenuItem>
                  <MenuItem value="0">Inactive</MenuItem>
                </TextField>
                <TextField
                  select
                  fullWidth
                  label="VIP"
                  value={data.is_vip ? '1' : '0'}
                  onChange={(event) => setData('is_vip', event.target.value === '1')}
                  error={Boolean(errors.is_vip)}
                  helperText={errors.is_vip}
                >
                  <MenuItem value="0">Free</MenuItem>
                  <MenuItem value="1">VIP</MenuItem>
                </TextField>
              </Stack>
            </Stack>
          </DialogContent>
          <DialogActions>
            <Button onClick={closeDialog} disabled={processing}>Cancel</Button>
            <Button type="submit" variant="contained" disabled={processing}>
              {editingCourse ? 'Update' : 'Create'}
            </Button>
          </DialogActions>
        </Box>
      </Dialog>

      <Snackbar open={Boolean(flash?.success)} autoHideDuration={3000} anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}>
        <Alert severity="success" variant="filled">
          {flash?.success || 'Action completed successfully.'}
        </Alert>
      </Snackbar>
    </Box>
  );
}

AdditionalCourses.layout = (page) => <AdminLayout children={page} />;
