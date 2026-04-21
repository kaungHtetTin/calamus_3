import React, { useMemo, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import {
  Alert,
  Box,
  Button,
  Card,
  CardContent,
  Chip,
  InputAdornment,
  Grid,
  IconButton,
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
  Search as SearchIcon,
  OpenInNew as OpenInNewIcon,
  School as SchoolIcon,
} from '@mui/icons-material';
export default function Courses({ courses = [] }) {
  const { admin_app_url, flash } = usePage().props;
  const [search, setSearch] = useState('');
  const [majorFilter, setMajorFilter] = useState('all');
  const [statusFilter, setStatusFilter] = useState('all');
  const [vipFilter, setVipFilter] = useState('all');

  const activeCount = useMemo(
    () => (Array.isArray(courses) ? courses.filter((course) => Number(course.active) === 1).length : 0),
    [courses]
  );
  const vipCount = useMemo(
    () => (Array.isArray(courses) ? courses.filter((course) => Number(course.is_vip) === 1).length : 0),
    [courses]
  );
  const majorOptions = useMemo(() => {
    return [...new Set((courses || []).map((course) => String(course.major || '').trim()).filter(Boolean))].sort();
  }, [courses]);
  const filteredCourses = useMemo(() => {
    const keyword = search.trim().toLowerCase();
    return (courses || []).filter((course) => {
      const major = String(course.major || '').trim().toLowerCase();
      const title = String(course.title || '').toLowerCase();
      const teacher = String(course.teacher?.name || '').toLowerCase();
      const idText = String(course.course_id || '');
      if (majorFilter !== 'all' && major !== majorFilter) return false;
      if (statusFilter === 'active' && Number(course.active) !== 1) return false;
      if (statusFilter === 'inactive' && Number(course.active) === 1) return false;
      if (vipFilter === 'vip' && Number(course.is_vip) !== 1) return false;
      if (vipFilter === 'nonvip' && Number(course.is_vip) === 1) return false;
      if (!keyword) return true;
      return title.includes(keyword) || teacher.includes(keyword) || major.includes(keyword) || idText.includes(keyword);
    });
  }, [courses, search, majorFilter, statusFilter, vipFilter]);

  const openCourseEditWorkspace = (course) => {
    window.location.href = `${admin_app_url}/courses/${course.course_id}/edit`;
  };

  return (
    <Box>
      <Head title="Course Management" />
      <Stack spacing={2.5}>
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              Course Management
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Create, update, and manage course records.
            </Typography>
          </Box>
          <Button component={Link} href={`${admin_app_url}/courses/create`} variant="contained" startIcon={<AddIcon />}>
            Create Course
          </Button>
        </Box>

        <Grid container spacing={1.5}>
          <Grid item xs={12} sm={6} md={3}>
            <Card variant="outlined">
              <CardContent sx={{ p: 1.5, '&:last-child': { pb: 1.5 } }}>
                <Typography variant="body2" color="text.secondary">Total Courses</Typography>
                <Typography variant="h5" sx={{ fontWeight: 700, mt: 0.5 }}>{courses.length}</Typography>
              </CardContent>
            </Card>
          </Grid>
          <Grid item xs={12} sm={6} md={3}>
            <Card variant="outlined">
              <CardContent sx={{ p: 1.5, '&:last-child': { pb: 1.5 } }}>
                <Typography variant="body2" color="text.secondary">Active Courses</Typography>
                <Typography variant="h5" sx={{ fontWeight: 700, mt: 0.5 }}>{activeCount}</Typography>
              </CardContent>
            </Card>
          </Grid>
          <Grid item xs={12} sm={6} md={3}>
            <Card variant="outlined">
              <CardContent sx={{ p: 1.5, '&:last-child': { pb: 1.5 } }}>
                <Typography variant="body2" color="text.secondary">VIP Courses</Typography>
                <Typography variant="h5" sx={{ fontWeight: 700, mt: 0.5 }}>{vipCount}</Typography>
              </CardContent>
            </Card>
          </Grid>
          <Grid item xs={12} sm={6} md={3}>
            <Card variant="outlined">
              <CardContent sx={{ p: 1.5, '&:last-child': { pb: 1.5 } }}>
                <Typography variant="body2" color="text.secondary">Filtered Result</Typography>
                <Typography variant="h5" sx={{ fontWeight: 700, mt: 0.5 }}>{filteredCourses.length}</Typography>
              </CardContent>
            </Card>
          </Grid>
        </Grid>

        <Paper variant="outlined" sx={{ borderRadius: 2, p: 1.5 }}>
          <Stack direction={{ xs: 'column', md: 'row' }} spacing={1}>
            <TextField
              size="small"
              fullWidth
              placeholder="Search by title, teacher, major, or ID"
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              InputProps={{
                startAdornment: (
                  <InputAdornment position="start">
                    <SearchIcon fontSize="small" />
                  </InputAdornment>
                ),
              }}
            />
            <TextField size="small" select label="Major" value={majorFilter} onChange={(event) => setMajorFilter(event.target.value)} sx={{ minWidth: 160 }}>
              <MenuItem value="all">All</MenuItem>
              {majorOptions.map((major) => (
                <MenuItem key={`major-filter-${major}`} value={major.toLowerCase()}>{major}</MenuItem>
              ))}
            </TextField>
            <TextField size="small" select label="Status" value={statusFilter} onChange={(event) => setStatusFilter(event.target.value)} sx={{ minWidth: 140 }}>
              <MenuItem value="all">All</MenuItem>
              <MenuItem value="active">Active</MenuItem>
              <MenuItem value="inactive">Inactive</MenuItem>
            </TextField>
            <TextField size="small" select label="VIP" value={vipFilter} onChange={(event) => setVipFilter(event.target.value)} sx={{ minWidth: 140 }}>
              <MenuItem value="all">All</MenuItem>
              <MenuItem value="vip">VIP</MenuItem>
              <MenuItem value="nonvip">Non VIP</MenuItem>
            </TextField>
          </Stack>
        </Paper>

        <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
          <Table size="small">
            <TableHead>
              <TableRow>
                <TableCell sx={{ fontWeight: 700 }}>Course</TableCell>
                <TableCell sx={{ fontWeight: 700 }}>Teacher</TableCell>
                <TableCell sx={{ fontWeight: 700 }}>Major</TableCell>
                <TableCell sx={{ fontWeight: 700 }}>Fee</TableCell>
                <TableCell sx={{ fontWeight: 700 }}>Status</TableCell>
                <TableCell align="right" sx={{ fontWeight: 700 }}>Workspace</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {filteredCourses.map((course) => (
                <TableRow key={course.course_id} hover>
                  <TableCell>
                    <Stack direction="row" spacing={1.25} alignItems="center">
                      <SchoolIcon fontSize="small" color="action" />
                      <Box>
                        <Typography variant="body2" sx={{ fontWeight: 700 }}>{course.title}</Typography>
                        <Typography variant="caption" color="text.secondary">
                          ID: {course.course_id} • {course.lessons_count} lessons
                        </Typography>
                      </Box>
                    </Stack>
                  </TableCell>
                  <TableCell>{course.teacher?.name || `#${course.teacher_id}`}</TableCell>
                  <TableCell>{course.major}</TableCell>
                  <TableCell>{course.fee}</TableCell>
                  <TableCell>
                    <Stack direction="row" spacing={0.75}>
                      <Chip size="small" label={Number(course.active) === 1 ? 'Active' : 'Inactive'} color={Number(course.active) === 1 ? 'success' : 'default'} />
                      {Number(course.is_vip) === 1 && <Chip size="small" label="VIP" color="warning" />}
                    </Stack>
                  </TableCell>
                  <TableCell align="right">
                    <IconButton size="small" onClick={() => openCourseEditWorkspace(course)} title="Open Course Workspace">
                      <OpenInNewIcon fontSize="small" />
                    </IconButton>
                  </TableCell>
                </TableRow>
              ))}
              {filteredCourses.length === 0 && (
                <TableRow>
                  <TableCell colSpan={6}>
                    <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                      No courses match current filters.
                    </Typography>
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </TableContainer>
      </Stack>
      <Snackbar open={Boolean(flash?.success)} autoHideDuration={3000} anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}>
        <Alert severity="success" variant="filled">
          {flash?.success || 'Action completed successfully.'}
        </Alert>
      </Snackbar>
    </Box>
  );
}

Courses.layout = (page) => <AdminLayout children={page} />;
