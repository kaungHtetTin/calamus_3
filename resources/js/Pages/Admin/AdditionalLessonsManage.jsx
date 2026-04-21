import React, { useMemo, useState } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import {
  Box,
  Grid,
  Paper,
  Stack,
  Typography,
  Button,
  Chip,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  TextField,
  Menu,
  MenuItem,
  Divider,
  Avatar,
} from '@mui/material';
import { Add as AddIcon, MoreVert as MoreIcon, UploadFile as UploadFileIcon } from '@mui/icons-material';

export default function AdditionalLessonsManage({ languages = [], selectedMajor = '', course = null, categories = [] }) {
  const { admin_app_url, flash } = usePage().props;
  const [major, setMajor] = useState(selectedMajor || (languages[0]?.code ?? ''));
  const [categoryDialogOpen, setCategoryDialogOpen] = useState(false);
  const [categoryImageName, setCategoryImageName] = useState('');

  const {
    data: categoryData,
    setData: setCategoryData,
    post: postCategory,
    processing: categoryProcessing,
    errors: categoryErrors,
    reset: resetCategory,
  } = useForm({
    category: '',
    category_title: '',
    category_image: null,
    image_url: '',
    sort_order: 0,
    category_major: major,
  });

  const {
    data: lessonData,
    setData: setLessonData,
    post: postLesson,
    processing: lessonProcessing,
    errors: lessonErrors,
    reset: resetLesson,
  } = useForm({
    lesson_type: 'video',
    title: '',
    title_mini: '',
    duration: 0,
    isVip: false,
    video_file: null,
    html_file: null,
    download_url: '',
    thumbnail_image: null,
    effective_major: major,
  });

  const [anchorEl, setAnchorEl] = useState(null);
  const [menuCategoryId, setMenuCategoryId] = useState(null);
  const openMenu = Boolean(anchorEl);
  const handleMenuOpen = (event, categoryId) => {
    setAnchorEl(event.currentTarget);
    setMenuCategoryId(categoryId);
  };
  const handleMenuClose = () => {
    setAnchorEl(null);
    setMenuCategoryId(null);
  };

  const handleCreateCategory = () => {
    setCategoryDialogOpen(true);
  };

  const handleCategoryImageChange = (e) => {
    const f = e.target.files?.[0] || null;
    setCategoryData('category_image', f);
    setCategoryImageName(f ? f.name : '');
  };

  const submitCreateCategory = () => {
    postCategory(`${admin_app_url}/courses/${course.course_id}/categories`, {
      onSuccess: () => {
        setCategoryDialogOpen(false);
        resetCategory();
        setCategoryImageName('');
        window.location.href = `${admin_app_url}/additional-lessons/${course.course_id}?major=${encodeURIComponent(major)}`;
      },
      forceFormData: true,
    });
  };

  const handleCreateVideoLesson = (categoryId) => {
    setLessonData('lesson_type', 'video');
    setLessonData('effective_major', major);
    postLesson(`${admin_app_url}/courses/${course.course_id}/categories/${categoryId}/lessons`, {
      onSuccess: () => {
        resetLesson();
        window.location.href = `${admin_app_url}/additional-lessons/${course.course_id}?major=${encodeURIComponent(major)}`;
      },
      forceFormData: true,
    });
  };

  const handleCreateDocumentLesson = (categoryId) => {
    setLessonData('lesson_type', 'document');
    setLessonData('effective_major', major);
    postLesson(`${admin_app_url}/courses/${course.course_id}/categories/${categoryId}/lessons`, {
      onSuccess: () => {
        resetLesson();
        window.location.href = `${admin_app_url}/additional-lessons/${course.course_id}?major=${encodeURIComponent(major)}`;
      },
      forceFormData: true,
    });
  };

  if (!course) {
    return (
      <AdminLayout>
        <Head title="Additional Lessons" />
        <Box sx={{ p: 2 }}>
          <Typography>Course not found.</Typography>
        </Box>
      </AdminLayout>
    );
  }

  return (
    <AdminLayout>
      <Head title={`Additional: ${course.title}`} />
      <Box sx={{ p: 2 }}>
        <Stack direction={{ xs: 'column', md: 'row' }} spacing={1.5} alignItems={{ xs: 'flex-start', md: 'center' }} justifyContent="space-between" sx={{ mb: 2 }}>
          <Stack direction="row" spacing={1} alignItems="center">
            <Typography variant="h6">{course.title}</Typography>
            <Chip label={`ID ${course.course_id}`} size="small" />
            <Chip label={course.is_vip ? 'VIP' : 'Free'} size="small" color={course.is_vip ? 'warning' : 'default'} />
          </Stack>
          <Stack direction="row" spacing={1}>
            <Button component={Link} href={`${admin_app_url}/additional-lessons?major=${encodeURIComponent(major)}`} size="small" variant="outlined">
              Back to list
            </Button>
            <Button onClick={handleCreateCategory} size="small" variant="contained" startIcon={<AddIcon />}>
              Add Category
            </Button>
          </Stack>
        </Stack>

        <Grid container spacing={1.5}>
          {categories.map((cat) => (
            <Grid item xs={12} md={6} key={cat.id}>
              <Paper variant="outlined" sx={{ p: 1.5 }}>
                <Stack direction="row" spacing={1} alignItems="center" justifyContent="space-between">
                  <Stack direction="row" spacing={1} alignItems="center">
                    <Avatar
                      src={cat.image_url || ''}
                      variant="rounded"
                      sx={{ width: 56, height: 32, bgcolor: 'action.hover' }}
                    />
                    <Box>
                      <Typography sx={{ fontWeight: 700 }}>{cat.category_title}</Typography>
                      <Typography variant="caption" color="text.secondary">{cat.category}</Typography>
                    </Box>
                  </Stack>
                  <Stack direction="row" spacing={1}>
                    <Button
                      size="small"
                      variant="outlined"
                      onClick={(e) => handleMenuOpen(e, cat.id)}
                      endIcon={<MoreIcon fontSize="small" />}
                    >
                      Add Lesson
                    </Button>
                  </Stack>
                </Stack>
                <Divider sx={{ my: 1 }} />
                <Stack spacing={0.5}>
                  {cat.lessons.map((l) => (
                    <Stack key={l.id} direction="row" spacing={1} justifyContent="space-between" alignItems="center">
                      <Typography noWrap sx={{ maxWidth: '65%' }}>{l.title}</Typography>
                      <Stack direction="row" spacing={1} alignItems="center">
                        <Chip size="small" label={l.isVideo ? 'Video' : 'Doc'} />
                        <Chip size="small" label={l.isVip ? 'VIP' : 'Free'} color={l.isVip ? 'warning' : 'default'} />
                      </Stack>
                    </Stack>
                  ))}
                  {cat.lessons.length === 0 && (
                    <Typography color="text.secondary" variant="body2">No lessons yet.</Typography>
                  )}
                </Stack>
              </Paper>
            </Grid>
          ))}
          {categories.length === 0 && (
            <Grid item xs={12}>
              <Paper variant="outlined" sx={{ p: 2 }}>
                <Typography color="text.secondary">No categories for this major. Create one to get started.</Typography>
              </Paper>
            </Grid>
          )}
        </Grid>
      </Box>

      <Menu anchorEl={anchorEl} open={openMenu} onClose={handleMenuClose}>
        <MenuItem
          onClick={() => {
            handleMenuClose();
            setLessonData('lesson_type', 'video');
            // Open file pickers by clicking hidden inputs below
            const videoInput = document.getElementById('al-video-file');
            const thumbInput = document.getElementById('al-thumb-file');
            if (videoInput) videoInput.click();
            if (thumbInput) thumbInput.click();
          }}
        >
          Add Video Lesson
        </MenuItem>
        <MenuItem
          onClick={() => {
            handleMenuClose();
            setLessonData('lesson_type', 'document');
            const docInput = document.getElementById('al-doc-file');
            if (docInput) docInput.click();
          }}
        >
          Add Document Lesson
        </MenuItem>
      </Menu>

      {/* Hidden file inputs to trigger uploads */}
      <input
        id="al-video-file"
        hidden
        type="file"
        accept="video/*"
        onChange={(e) => {
          const f = e.target.files?.[0] || null;
          setLessonData('video_file', f);
          if (menuCategoryId) {
            // Ask thumbnail next
            const thumbInput = document.getElementById('al-thumb-file');
            if (thumbInput) thumbInput.click();
          }
        }}
      />
      <input
        id="al-thumb-file"
        hidden
        type="file"
        accept="image/*"
        onChange={(e) => {
          const f = e.target.files?.[0] || null;
          setLessonData('thumbnail_image', f);
          if (menuCategoryId) {
            handleCreateVideoLesson(menuCategoryId);
          }
        }}
      />
      <input
        id="al-doc-file"
        hidden
        type="file"
        accept=".html,.htm,text/html"
        onChange={(e) => {
          const f = e.target.files?.[0] || null;
          setLessonData('html_file', f);
          if (menuCategoryId) {
            handleCreateDocumentLesson(menuCategoryId);
          }
        }}
      />

      <Dialog open={categoryDialogOpen} onClose={() => setCategoryDialogOpen(false)} fullWidth maxWidth="sm">
        <DialogTitle>Create Category</DialogTitle>
        <DialogContent dividers>
          <Stack spacing={1.25} sx={{ mt: 0.5 }}>
            <TextField
              label="Category Code"
              value={categoryData.category}
              onChange={(e) => setCategoryData('category', e.target.value)}
              error={Boolean(categoryErrors.category)}
              helperText={categoryErrors.category}
            />
            <TextField
              label="Category Title"
              value={categoryData.category_title}
              onChange={(e) => setCategoryData('category_title', e.target.value)}
              error={Boolean(categoryErrors.category_title)}
              helperText={categoryErrors.category_title}
            />
            <Button component="label" size="small" variant="outlined" startIcon={<UploadFileIcon />}>
              Upload Image
              <input hidden type="file" accept="image/*" onChange={handleCategoryImageChange} />
            </Button>
            {categoryImageName && (
              <Typography variant="caption" color="text.secondary">{categoryImageName}</Typography>
            )}
            <TextField
              type="number"
              label="Sort Order"
              value={categoryData.sort_order}
              onChange={(e) => setCategoryData('sort_order', Number(e.target.value))}
            />
          </Stack>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setCategoryDialogOpen(false)} size="small">Cancel</Button>
          <Button onClick={submitCreateCategory} size="small" variant="contained" disabled={categoryProcessing}>
            Create
          </Button>
        </DialogActions>
      </Dialog>
    </AdminLayout>
  );
}

