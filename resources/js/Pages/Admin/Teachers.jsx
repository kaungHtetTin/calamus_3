import React, { useMemo, useState } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import ImageCropper from '../../Components/Admin/ImageCropper';
import {
  Alert,
  Avatar,
  Box,
  Button,
  Card,
  CardContent,
  Chip,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  Divider,
  Grid,
  IconButton,
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
  Person as PersonIcon,
  UploadFile as UploadFileIcon,
  DeleteOutline as DeleteOutlineIcon,
} from '@mui/icons-material';

const defaultForm = {
  name: '',
  profile: '',
  profile_image: '',
  rank: 0,
  facebook: '',
  telegram: '',
  youtube: '',
  description: '',
  qualification: '',
  experience: 0,
  total_course: 0,
};

export default function Teachers({ teachers }) {
  const { admin_app_url, flash } = usePage().props;
  const [openDialog, setOpenDialog] = useState(false);
  const [editingTeacher, setEditingTeacher] = useState(null);
  const [openSnackbar, setOpenSnackbar] = useState(false);
  const [cropperOpen, setCropperOpen] = useState(false);
  const [tempImage, setTempImage] = useState(null);
  const [profilePreview, setProfilePreview] = useState('');

  const { data, setData, post, delete: destroy, processing, errors, reset, clearErrors } = useForm(defaultForm);

  const averageRank = useMemo(() => {
    if (!Array.isArray(teachers) || teachers.length === 0) {
      return 0;
    }
    const total = teachers.reduce((sum, teacher) => sum + Number(teacher.rank || 0), 0);
    return Number((total / teachers.length).toFixed(1));
  }, [teachers]);

  const handleOpenDialog = (teacher = null) => {
    clearErrors();
    if (teacher) {
      setEditingTeacher(teacher);
      setData({
        name: teacher.name || '',
        profile: teacher.profile || '',
        profile_image: '',
        rank: Number(teacher.rank || 0),
        facebook: teacher.facebook || '',
        telegram: teacher.telegram || '',
        youtube: teacher.youtube || '',
        description: teacher.description || '',
        qualification: teacher.qualification || '',
        experience: Number(teacher.experience || 0),
        total_course: Number(teacher.total_course || 0),
      });
      setProfilePreview(teacher.profile || '');
    } else {
      setEditingTeacher(null);
      reset();
      setData(defaultForm);
      setProfilePreview('');
    }
    setOpenDialog(true);
  };

  const handleCloseDialog = () => {
    setOpenDialog(false);
    setEditingTeacher(null);
    reset();
    setData(defaultForm);
    setCropperOpen(false);
    setTempImage(null);
    setProfilePreview('');
  };

  const handleSubmit = (event) => {
    event.preventDefault();
    const payload = {
      ...data,
      rank: Number(data.rank || 0),
      experience: Number(data.experience || 0),
      total_course: Number(data.total_course || 0),
    };

    if (editingTeacher) {
      post(`${admin_app_url}/teachers/${editingTeacher.id}`, {
        data: {
          ...payload,
          _method: 'patch',
        },
        onSuccess: () => {
          handleCloseDialog();
          setOpenSnackbar(true);
        },
      });
      return;
    }

    post(`${admin_app_url}/teachers`, {
      data: payload,
      onSuccess: () => {
        handleCloseDialog();
        setOpenSnackbar(true);
      },
    });
  };

  const handleProfileFileChange = (event) => {
    const file = event.target.files?.[0];
    if (!file) {
      return;
    }

    const reader = new FileReader();
    reader.onload = () => {
      setTempImage(reader.result);
      setCropperOpen(true);
    };
    reader.readAsDataURL(file);
  };

  const handleCropComplete = (blob) => {
    const reader = new FileReader();
    reader.readAsDataURL(blob);
    reader.onloadend = () => {
      const base64data = reader.result;
      setData('profile_image', base64data);
      setProfilePreview(base64data);
    };
    setCropperOpen(false);
    setTempImage(null);
  };

  const handleClearProfileImage = () => {
    if (editingTeacher?.profile) {
      setData('profile', editingTeacher.profile);
      setData('profile_image', '');
      setProfilePreview(editingTeacher.profile);
      return;
    }
    setData('profile', '');
    setData('profile_image', '');
    setProfilePreview('');
  };

  const handleDelete = (teacher) => {
    if (!confirm(`Delete teacher "${teacher.name}"?`)) {
      return;
    }
    destroy(`${admin_app_url}/teachers/${teacher.id}`, {
      onSuccess: () => setOpenSnackbar(true),
    });
  };

  return (
    <Box>
      <Head title="Teacher Management" />
      <Stack spacing={2.5}>
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              Teacher Management
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Manage teacher profiles used across courses.
            </Typography>
          </Box>
          <Button variant="contained" startIcon={<AddIcon />} onClick={() => handleOpenDialog()}>
            Add Teacher
          </Button>
        </Box>

        <Grid container spacing={1.5}>
          <Grid item xs={12} sm={6} md={3}>
            <Card variant="outlined">
              <CardContent sx={{ p: 1.5, '&:last-child': { pb: 1.5 } }}>
                <Typography variant="body2" color="text.secondary">Total Teachers</Typography>
                <Typography variant="h5" sx={{ fontWeight: 700, mt: 0.5 }}>{teachers.length}</Typography>
              </CardContent>
            </Card>
          </Grid>
          <Grid item xs={12} sm={6} md={3}>
            <Card variant="outlined">
              <CardContent sx={{ p: 1.5, '&:last-child': { pb: 1.5 } }}>
                <Typography variant="body2" color="text.secondary">Average Rank</Typography>
                <Typography variant="h5" sx={{ fontWeight: 700, mt: 0.5 }}>{averageRank}</Typography>
              </CardContent>
            </Card>
          </Grid>
        </Grid>

        <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
          <Table size="small">
            <TableHead>
              <TableRow>
                <TableCell sx={{ fontWeight: 700 }}>Teacher</TableCell>
                <TableCell sx={{ fontWeight: 700 }}>Rank</TableCell>
                <TableCell sx={{ fontWeight: 700 }}>Experience</TableCell>
                <TableCell sx={{ fontWeight: 700 }}>Total Course</TableCell>
                <TableCell sx={{ fontWeight: 700 }}>Links</TableCell>
                <TableCell align="right" sx={{ fontWeight: 700 }}>Actions</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {teachers.map((teacher) => (
                <TableRow key={teacher.id} hover>
                  <TableCell>
                    <Stack direction="row" spacing={1.25} alignItems="center">
                      <Avatar src={teacher.profile || ''} sx={{ width: 30, height: 30 }}>
                        <PersonIcon fontSize="small" />
                      </Avatar>
                      <Box>
                        <Typography variant="body2" sx={{ fontWeight: 700 }}>{teacher.name}</Typography>
                        <Typography variant="caption" color="text.secondary" noWrap sx={{ display: 'block', maxWidth: 280 }}>
                          {teacher.qualification || '-'}
                        </Typography>
                      </Box>
                    </Stack>
                  </TableCell>
                  <TableCell>{teacher.rank}</TableCell>
                  <TableCell>{teacher.experience}</TableCell>
                  <TableCell>{teacher.total_course}</TableCell>
                  <TableCell>
                    <Stack direction="row" spacing={0.75} useFlexGap flexWrap="wrap">
                      {teacher.facebook ? <Chip size="small" label="Facebook" variant="outlined" /> : null}
                      {teacher.telegram ? <Chip size="small" label="Telegram" variant="outlined" /> : null}
                      {teacher.youtube ? <Chip size="small" label="YouTube" variant="outlined" /> : null}
                    </Stack>
                  </TableCell>
                  <TableCell align="right">
                    <IconButton size="small" onClick={() => handleOpenDialog(teacher)}>
                      <EditIcon fontSize="small" />
                    </IconButton>
                    <IconButton size="small" color="error" onClick={() => handleDelete(teacher)}>
                      <DeleteIcon fontSize="small" />
                    </IconButton>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </TableContainer>
      </Stack>

      <Dialog open={openDialog} onClose={handleCloseDialog} maxWidth="md" fullWidth>
        <DialogTitle>{editingTeacher ? 'Edit Teacher' : 'Add Teacher'}</DialogTitle>
        <Box component="form" onSubmit={handleSubmit}>
          <DialogContent dividers>
            <Grid container spacing={2}>
              <Grid item xs={12} md={4}>
                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2, height: '100%' }}>
                  <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1.25 }}>
                    Profile Image
                  </Typography>
                  <Stack spacing={1.5} alignItems="center">
                    <Avatar src={profilePreview || data.profile || ''} sx={{ width: 110, height: 110 }}>
                      <PersonIcon sx={{ fontSize: 42 }} />
                    </Avatar>
                    <Typography variant="caption" align="center" color={errors.profile ? 'error.main' : 'text.secondary'}>
                      {errors.profile || 'Square crop with centered face works best.'}
                    </Typography>
                    <Stack direction="row" spacing={1}>
                      <Button size="small" variant="contained" component="label" startIcon={<UploadFileIcon />}>
                        Upload
                        <input hidden type="file" accept="image/*" onChange={handleProfileFileChange} />
                      </Button>
                      <Button size="small" variant="outlined" startIcon={<DeleteOutlineIcon />} onClick={handleClearProfileImage}>
                        Reset
                      </Button>
                    </Stack>
                  </Stack>
                  <Divider sx={{ my: 2 }} />
                  <Stack spacing={1.25}>
                    <TextField fullWidth size="small" type="number" label="Rank" value={data.rank} onChange={(event) => setData('rank', event.target.value)} error={Boolean(errors.rank)} helperText={errors.rank} />
                    <TextField fullWidth size="small" type="number" label="Experience" value={data.experience} onChange={(event) => setData('experience', event.target.value)} error={Boolean(errors.experience)} helperText={errors.experience} />
                    <TextField fullWidth size="small" type="number" label="Total Course" value={data.total_course} onChange={(event) => setData('total_course', event.target.value)} error={Boolean(errors.total_course)} helperText={errors.total_course} />
                  </Stack>
                </Paper>
              </Grid>
              <Grid item xs={12} md={8}>
                <Stack spacing={2}>
                  <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                    <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1.25 }}>Basic Information</Typography>
                    <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: 'repeat(2, minmax(0, 1fr))' }, gap: 1.5 }}>
                      <TextField fullWidth size="small" label="Name" value={data.name} onChange={(event) => setData('name', event.target.value)} error={Boolean(errors.name)} helperText={errors.name} />
                      <TextField fullWidth size="small" label="Qualification" value={data.qualification} onChange={(event) => setData('qualification', event.target.value)} error={Boolean(errors.qualification)} helperText={errors.qualification} />
                    </Box>
                  </Paper>
                  <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                    <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1.25 }}>Social Links</Typography>
                    <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: 'repeat(2, minmax(0, 1fr))' }, gap: 1.5 }}>
                      <TextField fullWidth size="small" label="Facebook URL" value={data.facebook} onChange={(event) => setData('facebook', event.target.value)} error={Boolean(errors.facebook)} helperText={errors.facebook} />
                      <TextField fullWidth size="small" label="Telegram URL" value={data.telegram} onChange={(event) => setData('telegram', event.target.value)} error={Boolean(errors.telegram)} helperText={errors.telegram} />
                      <TextField fullWidth size="small" label="YouTube URL" value={data.youtube} onChange={(event) => setData('youtube', event.target.value)} error={Boolean(errors.youtube)} helperText={errors.youtube} sx={{ gridColumn: { xs: 'span 1', md: 'span 2' } }} />
                    </Box>
                  </Paper>
                  <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                    <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1.25 }}>Bio</Typography>
                    <TextField fullWidth multiline minRows={5} label="Description" value={data.description} onChange={(event) => setData('description', event.target.value)} error={Boolean(errors.description)} helperText={errors.description} />
                  </Paper>
                </Stack>
              </Grid>
            </Grid>
          </DialogContent>
          <DialogActions>
            <Button onClick={handleCloseDialog} disabled={processing}>Cancel</Button>
            <Button type="submit" variant="contained" disabled={processing}>
              {editingTeacher ? 'Update' : 'Create'}
            </Button>
          </DialogActions>
        </Box>
      </Dialog>

      <Snackbar open={openSnackbar || Boolean(flash?.success)} autoHideDuration={3000} onClose={() => setOpenSnackbar(false)} anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}>
        <Alert severity="success" variant="filled" onClose={() => setOpenSnackbar(false)}>
          {flash?.success || 'Action completed successfully.'}
        </Alert>
      </Snackbar>

      <ImageCropper
        open={cropperOpen}
        image={tempImage}
        onCropComplete={handleCropComplete}
        onCancel={() => {
          setCropperOpen(false);
          setTempImage(null);
        }}
      />
    </Box>
  );
}

Teachers.layout = (page) => <AdminLayout children={page} />;
