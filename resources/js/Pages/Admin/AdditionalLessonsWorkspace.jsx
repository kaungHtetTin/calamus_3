import React, { useEffect, useMemo, useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import {
  Alert,
  Avatar,
  Box,
  Button,
  Chip,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  Divider,
  IconButton,
  List,
  ListItem,
  ListItemButton,
  ListItemIcon,
  ListItemText,
  MenuItem,
  Menu,
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
  useMediaQuery,
  useTheme,
} from '@mui/material';
import {
  Add as AddIcon,
  AutoAwesome as AutoAwesomeIcon,
  Construction as ConstructionIcon,
  Delete as DeleteIcon,
  Edit as EditIcon,
  Language as LanguageIcon,
  MoreVert as MoreVertIcon,
  OpenInNew as OpenInNewIcon,
  School as SchoolIcon,
} from '@mui/icons-material';
import VideoLessonCreateForm from './CourseEdit/VideoLessonCreateForm';
import DocumentLessonCreateForm from './CourseEdit/DocumentLessonCreateForm';
import BulkDocumentLessonCreateForm from './CourseEdit/BulkDocumentLessonCreateForm';

const defaultCategoryForm = {
  category: '',
  category_title: '',
  image_url: '',
  category_image: null,
  sort_order: 0,
  category_major: '',
};

const defaultLessonForm = {
  lesson_type: 'video',
  title: '',
  title_mini: '',
  duration: 0,
  isVip: false,
  isVideo: true,
  link: '',
  document_link: '',
  download_url: '',
  thumbnail: '',
  notes: '',
  date: 0,
  video_file: null,
  html_file: null,
  html_files: [],
  thumbnail_image: null,
  effective_major: '',
};

const createEmptyLectureNote = () => ({
  hour: '',
  minute: '',
  second: '',
  note: '',
});

export default function AdditionalLessonsWorkspace({
  languages = [],
  selectedMajor = '',
  selectedLanguage = null,
  courses = [],
  allCourses = [],
  course = null,
  courseInScope = false,
  categories = [],
}) {
  const { admin_app_url, flash } = usePage().props;
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('md'));
  const major = selectedMajor || (languages[0]?.code ?? '');
  const appBaseUrl = admin_app_url.replace(/\/admin\/?$/, '');

  const buildImageUrl = (path) => {
    if (!path) {
      return '';
    }
    if (path.startsWith('http://') || path.startsWith('https://')) {
      return path;
    }
    const normalizedPath = path.startsWith('/') ? path : `/${path}`;
    return `${appBaseUrl}${normalizedPath}`;
  };

  const openWorkspaceFor = (nextMajor, nextCourseId) => {
    const majorParam = encodeURIComponent(nextMajor);
    const courseParam = nextCourseId ? `&courseId=${encodeURIComponent(String(nextCourseId))}` : '';
    window.location.href = `${admin_app_url}/additional-lessons/workspace?major=${majorParam}${courseParam}`;
  };

  const scopedCourseIds = useMemo(() => new Set((courses || []).map((c) => Number(c.course_id))), [courses]);
  const availableCourses = useMemo(
    () => (allCourses || []).filter((c) => !scopedCourseIds.has(Number(c.course_id))),
    [allCourses, scopedCourseIds]
  );

  const [selectedCategoryId, setSelectedCategoryId] = useState(categories[0]?.id || null);
  const selectedCategory = useMemo(
    () => categories.find((item) => item.id === selectedCategoryId) || null,
    [categories, selectedCategoryId]
  );

  useEffect(() => {
    if (!categories.length) {
      setSelectedCategoryId(null);
      return;
    }
    const exists = categories.some((item) => item.id === selectedCategoryId);
    if (!exists) {
      setSelectedCategoryId(categories[0].id);
    }
  }, [categories, selectedCategoryId]);

  const {
    data: categoryData,
    setData: setCategoryData,
    post: postCategory,
    delete: deleteCategory,
    processing: categoryProcessing,
    errors: categoryErrors,
    reset: resetCategory,
    clearErrors: clearCategoryErrors,
  } = useForm({ ...defaultCategoryForm, category_major: major });

  const {
    data: lessonData,
    setData: setLessonData,
    post: postLesson,
    transform: transformLesson,
    delete: deleteLesson,
    processing: lessonProcessing,
    errors: lessonErrors,
    reset: resetLesson,
    clearErrors: clearLessonErrors,
  } = useForm({ ...defaultLessonForm, effective_major: major });

  const [categoryDialogOpen, setCategoryDialogOpen] = useState(false);
  const [editingCategory, setEditingCategory] = useState(null);
  const [categoryMenuAnchorEl, setCategoryMenuAnchorEl] = useState(null);
  const [categoryMenuTarget, setCategoryMenuTarget] = useState(null);
  const [categoryImagePreview, setCategoryImagePreview] = useState('');
  const [categoryImageName, setCategoryImageName] = useState('');

  const [lessonDialogOpen, setLessonDialogOpen] = useState(false);
  const [editingLesson, setEditingLesson] = useState(null);
  const [lessonMenuAnchorEl, setLessonMenuAnchorEl] = useState(null);
  const [lessonMenuTarget, setLessonMenuTarget] = useState(null);
  const [lessonTypeSelectorOpen, setLessonTypeSelectorOpen] = useState(false);
  const [newLessonType, setNewLessonType] = useState('video');
  const [createLessonFormOpen, setCreateLessonFormOpen] = useState(false);
  const [createLessonThumbPreview, setCreateLessonThumbPreview] = useState('');
  const [createLessonThumbName, setCreateLessonThumbName] = useState('');
  const [createLessonVideoName, setCreateLessonVideoName] = useState('');
  const [createLessonDocumentName, setCreateLessonDocumentName] = useState('');
  const [bulkUploadItems, setBulkUploadItems] = useState([]);
  const [bulkUploading, setBulkUploading] = useState(false);
  const [lectureNotes, setLectureNotes] = useState([createEmptyLectureNote()]);

  const firstLessonErrorMessage = useMemo(() => {
    const values = Object.values(lessonErrors || {});
    if (!values.length) {
      return '';
    }
    const firstValue = values[0];
    if (Array.isArray(firstValue)) {
      return String(firstValue[0] || '');
    }
    return String(firstValue || '');
  }, [lessonErrors]);

  const formatDurationHms = (totalSeconds) => {
    const safeSeconds = Math.max(0, Number(totalSeconds || 0));
    const hour = Math.floor(safeSeconds / 3600);
    const minute = Math.floor((safeSeconds % 3600) / 60);
    const second = safeSeconds % 60;
    const hourText = hour > 0 ? `${hour}h ` : '';
    const minuteText = `${minute}m `;
    const secondText = `${second}s`;
    return `${hourText}${minuteText}${secondText}`.trim();
  };

  const closeCategoryMenu = () => {
    setCategoryMenuAnchorEl(null);
    setCategoryMenuTarget(null);
  };

  const openCreateCategory = () => {
    setEditingCategory(null);
    clearCategoryErrors();
    resetCategory();
    setCategoryData({ ...defaultCategoryForm, category_major: major });
    setCategoryImagePreview('');
    setCategoryImageName('');
    setCategoryDialogOpen(true);
  };

  const openEditCategory = (cat) => {
    setEditingCategory(cat);
    clearCategoryErrors();
    setCategoryData({
      category: cat.category || '',
      category_title: cat.category_title || '',
      image_url: cat.image_url || '',
      category_image: null,
      sort_order: Number(cat.sort_order || 0),
      category_major: major,
    });
    setCategoryImagePreview(cat.image_url || '');
    setCategoryImageName(cat.image_url ? String(cat.image_url).split('/').pop() : '');
    setCategoryDialogOpen(true);
  };

  const submitCategory = (event) => {
    event.preventDefault();
    if (!course) {
      return;
    }

    const payload = {
      ...categoryData,
      sort_order: Number(categoryData.sort_order || 0),
      category_major: major,
    };

    const querySuffix = `?major=${encodeURIComponent(major)}`;
    if (editingCategory) {
      postCategory(`${admin_app_url}/courses/${course.course_id}/categories/${editingCategory.id}${querySuffix}`, {
        forceFormData: true,
        data: { ...payload, _method: 'patch' },
        onSuccess: () => {
          setCategoryDialogOpen(false);
          setEditingCategory(null);
          setCategoryImagePreview('');
          setCategoryImageName('');
        },
      });
      return;
    }

    postCategory(`${admin_app_url}/courses/${course.course_id}/categories${querySuffix}`, {
      forceFormData: true,
      data: payload,
      onSuccess: () => {
        setCategoryDialogOpen(false);
        setCategoryImagePreview('');
        setCategoryImageName('');
      },
    });
  };

  const handleCategoryImageChange = (event) => {
    const file = event.target.files?.[0] || null;
    setCategoryData('category_image', file);
    if (!file) {
      setCategoryImageName('');
      return;
    }
    setCategoryImageName(file.name);
    setCategoryImagePreview(URL.createObjectURL(file));
  };

  const removeCategory = (cat) => {
    if (!course) {
      return;
    }
    if (!confirm(`Delete category "${cat.category_title}" and its lessons?`)) {
      return;
    }
    deleteCategory(`${admin_app_url}/courses/${course.course_id}/categories/${cat.id}?major=${encodeURIComponent(major)}`);
  };

  const openCategoryMenu = (event, cat) => {
    event.stopPropagation();
    setCategoryMenuAnchorEl(event.currentTarget);
    setCategoryMenuTarget(cat);
  };

  const openLessonMenu = (event, lesson) => {
    event.stopPropagation();
    setLessonMenuAnchorEl(event.currentTarget);
    setLessonMenuTarget(lesson);
  };

  const closeLessonMenu = () => {
    setLessonMenuAnchorEl(null);
    setLessonMenuTarget(null);
  };

  const openEditLesson = (lesson) => {
    setEditingLesson(lesson);
    clearLessonErrors();
    const isVideoLesson = Number(lesson.isVideo || 0) === 1;
    let parsedLectureNotes = [createEmptyLectureNote()];
    if (isVideoLesson) {
      try {
        const parsed = JSON.parse(lesson.notes || '[]');
        if (Array.isArray(parsed) && parsed.length) {
          parsedLectureNotes = parsed
            .filter((item) => item && String(item.note || '').trim() !== '')
            .map((item) => {
              const totalSeconds = Number(item.time || 0);
              const hour = Math.floor(totalSeconds / 3600);
              const minute = Math.floor((totalSeconds % 3600) / 60);
              const second = totalSeconds % 60;
              return {
                hour: String(hour),
                minute: String(minute),
                second: String(second),
                note: String(item.note || ''),
              };
            });
          if (!parsedLectureNotes.length) {
            parsedLectureNotes = [createEmptyLectureNote()];
          }
        }
      } catch (error) {
        parsedLectureNotes = [{ ...createEmptyLectureNote(), note: String(lesson.notes || '') }];
      }
    }

    setLessonData({
      ...defaultLessonForm,
      title: lesson.title || '',
      title_mini: lesson.title_mini || '',
      duration: Number(lesson.duration || 0),
      isVip: Number(lesson.isVip || 0) === 1,
      isVideo: Number(lesson.isVideo || 0) === 1,
      link: lesson.link || '',
      document_link: lesson.document_link || '',
      download_url: lesson.download_url || '',
      thumbnail: lesson.thumbnail || '',
      notes: lesson.notes || '',
      date: Number(lesson.date || 0),
      video_file: null,
      html_file: null,
      html_files: [],
      thumbnail_image: null,
      effective_major: major,
    });
    setCreateLessonVideoName('');
    setCreateLessonDocumentName(lesson.document_link ? String(lesson.document_link).split('/').pop() : '');
    setCreateLessonThumbName('');
    setCreateLessonThumbPreview('');
    setLectureNotes(parsedLectureNotes);
    setLessonDialogOpen(true);
  };

  const submitLesson = (event) => {
    event.preventDefault();
    if (!course || !selectedCategory || !editingLesson) {
      return;
    }

    const endpoint = `${admin_app_url}/courses/${course.course_id}/categories/${selectedCategory.id}/lessons/${editingLesson.id}?major=${encodeURIComponent(major)}`;
    const editingVideoLesson = Number(editingLesson.isVideo || 0) === 1;
    if (editingVideoLesson) {
      const serializedNotes = lectureNotes
        .filter((item) => String(item.note || '').trim() !== '')
        .map((item) => {
          const hour = Number(item.hour || 0);
          const minute = Number(item.minute || 0);
          const second = Number(item.second || 0);
          const time = hour * 60 * 60 + minute * 60 + second;
          return {
            time,
            note: String(item.note || '').trim(),
          };
        });

      const payload = {
        title: lessonData.title,
        title_mini: lessonData.title_mini,
        duration: Number(lessonData.duration || 0),
        isVip: Boolean(lessonData.isVip),
        isVideo: true,
        notes: JSON.stringify(serializedNotes || []),
        date: Number(lessonData.date || 0),
        effective_major: major,
      };

      if (lessonData.video_file) {
        payload.video_file = lessonData.video_file;
      }
      if (lessonData.thumbnail_image) {
        payload.thumbnail_image = lessonData.thumbnail_image;
      }

      transformLesson(() => ({ ...payload, _method: 'patch' }));
      postLesson(endpoint, {
        forceFormData: true,
        onSuccess: () => {
          setLessonDialogOpen(false);
          setEditingLesson(null);
        },
        onFinish: () => {
          transformLesson((data) => data);
        },
      });
      return;
    }

    const payload = {
      title: lessonData.title,
      title_mini: lessonData.title_mini,
      duration: Number(lessonData.duration || 0),
      date: Number(lessonData.date || 0),
      isVip: lessonData.isVip ? 1 : 0,
      isVideo: 0,
      effective_major: major,
    };
    if (lessonData.html_file) {
      payload.html_file = lessonData.html_file;
    }
    transformLesson(() => ({ ...payload, _method: 'patch' }));
    postLesson(endpoint, {
      forceFormData: true,
      onSuccess: () => {
        setLessonDialogOpen(false);
        setEditingLesson(null);
      },
      onFinish: () => {
        transformLesson((data) => data);
      },
    });
  };

  const removeLesson = (lesson) => {
    if (!course || !selectedCategory) {
      return;
    }
    if (!confirm(`Delete lesson "${lesson.title}"?`)) {
      return;
    }
    deleteLesson(`${admin_app_url}/courses/${course.course_id}/categories/${selectedCategory.id}/lessons/${lesson.id}?major=${encodeURIComponent(major)}`);
  };

  const openLessonTypeSelector = () => {
    if (!selectedCategory) {
      return;
    }
    setLessonTypeSelectorOpen(true);
  };

  const openLessonPlaceholder = (type) => {
    setNewLessonType(type);
    setLessonTypeSelectorOpen(false);
    resetLesson();
    setLessonData({
      ...defaultLessonForm,
      lesson_type: type,
      isVideo: type === 'video',
      title_mini: selectedCategory?.category_title || '',
      duration: 0,
      date: Date.now(),
      html_file: null,
      html_files: [],
      effective_major: major,
    });
    setCreateLessonThumbPreview('');
    setCreateLessonThumbName('');
    setCreateLessonVideoName('');
    setCreateLessonDocumentName('');
    setBulkUploadItems([]);
    setLectureNotes([createEmptyLectureNote()]);
    setCreateLessonFormOpen(true);
  };

  const closeCreateLessonForm = () => {
    setCreateLessonFormOpen(false);
    setCreateLessonThumbPreview('');
    setCreateLessonThumbName('');
    setCreateLessonVideoName('');
    setCreateLessonDocumentName('');
    setBulkUploadItems([]);
    setBulkUploading(false);
    setLectureNotes([createEmptyLectureNote()]);
    resetLesson();
  };

  const handleCreateLessonVideoChange = (event) => {
    const file = event.target.files?.[0] || null;
    setLessonData('video_file', file);
    setCreateLessonVideoName(file ? file.name : '');
  };

  const handleCreateLessonThumbnailChange = (event) => {
    const file = event.target.files?.[0] || null;
    setLessonData('thumbnail_image', file);
    setCreateLessonThumbName(file ? file.name : '');
    setCreateLessonThumbPreview(file ? URL.createObjectURL(file) : '');
  };

  const handleDocumentLessonFileChange = (event) => {
    const file = event.target.files?.[0] || null;
    setLessonData('html_file', file);
    setCreateLessonDocumentName(file ? file.name : '');
  };

  const handleBulkDocumentFilesChange = (event) => {
    const files = Array.from(event.target.files || []).filter(Boolean);
    setLessonData('html_files', files);
    setBulkUploadItems(files.map((file) => ({ name: file.name, status: 'pending', progress: 0, error: '' })));
  };

  const addLectureNote = () => setLectureNotes((prev) => [...prev, createEmptyLectureNote()]);
  const removeLectureNoteItem = (index) => setLectureNotes((prev) => prev.filter((_, itemIndex) => itemIndex !== index));
  const changeLectureNote = (index, key, value) => {
    setLectureNotes((prev) => prev.map((entry, itemIndex) => (itemIndex === index ? { ...entry, [key]: value } : entry)));
  };

  const handleCreateLessonSubmit = async (event) => {
    event.preventDefault();
    if (!course || !selectedCategory) {
      return;
    }

    const endpoint = `${admin_app_url}/courses/${course.course_id}/categories/${selectedCategory.id}/lessons?major=${encodeURIComponent(major)}`;

    if (newLessonType === 'video') {
      const payload = {
        lesson_type: newLessonType,
        title: lessonData.title,
        title_mini: lessonData.title_mini,
        isVip: Boolean(lessonData.isVip),
        isVideo: true,
        duration: Number(lessonData.duration || 0),
        date: Date.now(),
        notes: JSON.stringify(
          lectureNotes
            .filter((item) => String(item.note || '').trim() !== '')
            .map((item) => {
              const hour = Number(item.hour || 0);
              const minute = Number(item.minute || 0);
              const second = Number(item.second || 0);
              const time = hour * 60 * 60 + minute * 60 + second;
              return {
                time,
                note: String(item.note || '').trim(),
              };
            })
        ),
        video_file: lessonData.video_file,
        thumbnail_image: lessonData.thumbnail_image,
        effective_major: major,
      };
      postLesson(endpoint, {
        forceFormData: true,
        data: payload,
        onSuccess: () => {
          closeCreateLessonForm();
        },
      });
      return;
    }

    if (newLessonType === 'document-bulk') {
      const files = lessonData.html_files || [];
      if (!files.length) {
        return;
      }

      setBulkUploading(true);
      setBulkUploadItems((prev) => prev.map((item) => ({ ...item, status: 'pending', progress: 0, error: '' })));
      clearLessonErrors();

      const getFileTitle = (file, index) => {
        const name = String(file?.name || '');
        const dot = name.lastIndexOf('.');
        const title = (dot > 0 ? name.slice(0, dot) : name).trim();
        return title || `Document Lesson ${index + 1}`;
      };

      let hasFailed = false;
      for (let index = 0; index < files.length; index += 1) {
        const file = files[index];
        if (!file) {
          continue;
        }

        setBulkUploadItems((prev) => prev.map((item, itemIndex) => (
          itemIndex === index ? { ...item, status: 'uploading', progress: 0, error: '' } : item
        )));

        const payload = {
          lesson_type: 'document',
          title: getFileTitle(file, index),
          title_mini: lessonData.title_mini,
          isVip: Boolean(lessonData.isVip),
          isVideo: false,
          html_file: file,
          effective_major: major,
        };

        const uploaded = await new Promise((resolve) => {
          transformLesson(() => payload);
          postLesson(endpoint, {
            forceFormData: true,
            preserveScroll: true,
            onProgress: (progress) => {
              const percentage = Number(progress?.percentage || 0);
              setBulkUploadItems((prev) => prev.map((item, itemIndex) => (
                itemIndex === index ? { ...item, progress: percentage } : item
              )));
            },
            onSuccess: () => {
              setBulkUploadItems((prev) => prev.map((item, itemIndex) => (
                itemIndex === index ? { ...item, status: 'done', progress: 100 } : item
              )));
              transformLesson((data) => data);
              resolve(true);
            },
            onError: (errors) => {
              const message = String(Object.values(errors || {})[0] || 'Upload failed');
              setBulkUploadItems((prev) => prev.map((item, itemIndex) => (
                itemIndex === index ? { ...item, status: 'error', error: message } : item
              )));
              transformLesson((data) => data);
              resolve(false);
            },
            onCancel: () => {
              transformLesson((data) => data);
            },
          });
        });

        if (!uploaded) {
          hasFailed = true;
        }
      }

      setBulkUploading(false);
      if (!hasFailed) {
        closeCreateLessonForm();
      }
      return;
    }

    {
      const payload = {
        lesson_type: newLessonType,
        title: lessonData.title,
        title_mini: lessonData.title_mini,
        isVip: Boolean(lessonData.isVip),
        isVideo: false,
        html_file: lessonData.html_file,
        effective_major: major,
      };
      postLesson(endpoint, {
        forceFormData: true,
        data: payload,
        onSuccess: () => {
          closeCreateLessonForm();
        },
      });
    }
  };

  return (
    <Box>
      <Head title="Additional Lessons Workspace" />
      <Stack spacing={2.5}>
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              Additional Lessons Workspace
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Channel: {major || '-'}
            </Typography>
          </Box>
        </Box>

        <Box sx={{ display: { xs: 'block', md: 'flex' }, gap: 2, alignItems: 'flex-start' }}>
          <Stack spacing={1.5} sx={{ flex: 1, minWidth: 0 }}>
            {!course ? (
              <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                <Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 0.75 }}>
                  Select a course
                </Typography>
                <Typography variant="body2" color="text.secondary">
                  Pick a course from the sidebar to manage categories and lessons for this channel.
                </Typography>
              </Paper>
            ) : (
              <>
                {!courseInScope && (
                  <Alert severity="info">
                    This course is not assigned to this channel yet. Create the first category to attach it (category.major = {major}).
                  </Alert>
                )}
                {Boolean(flash?.success) && (
                  <Alert severity="success">{flash.success}</Alert>
                )}
                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                  <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1.5 }}>
                    <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                      Lessons Categories
                    </Typography>
                    <Button size="small" variant="contained" startIcon={<AddIcon />} onClick={openCreateCategory}>
                      Add Category
                    </Button>
                  </Stack>
                  <Box
                    sx={{
                      display: 'grid',
                      gridTemplateColumns: { xs: 'repeat(3, minmax(0, 1fr))', sm: 'repeat(4, minmax(0, 1fr))', md: 'repeat(5, minmax(0, 1fr))', lg: 'repeat(6, minmax(0, 1fr))' },
                      gap: 0.75,
                    }}
                  >
                    {categories.map((cat) => (
                      <Paper
                        key={cat.id}
                        variant={selectedCategoryId === cat.id ? 'elevation' : 'outlined'}
                        elevation={selectedCategoryId === cat.id ? 2 : 0}
                        sx={{
                          p: 0.75,
                          borderRadius: 1.25,
                          cursor: 'pointer',
                          borderColor: selectedCategoryId === cat.id ? 'primary.main' : 'divider',
                        }}
                        onClick={() => setSelectedCategoryId(cat.id)}
                      >
                        <Stack spacing={0.5}>
                          <Box
                            sx={{
                              width: '100%',
                              aspectRatio: '1 / 1',
                              borderRadius: 1,
                              bgcolor: 'action.hover',
                              overflow: 'hidden',
                              display: 'flex',
                              alignItems: 'center',
                              justifyContent: 'center',
                            }}
                          >
                            {cat.image_url ? (
                              <Box component="img" src={cat.image_url} alt={cat.category_title} sx={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                            ) : (
                              <SchoolIcon color="action" sx={{ fontSize: 14 }} />
                            )}
                          </Box>
                          <Typography variant="caption" sx={{ fontWeight: 700, lineHeight: 1.2 }} noWrap>
                            {cat.category_title}
                          </Typography>
                          <Typography variant="caption" color="text.secondary" noWrap sx={{ fontSize: 10.5, lineHeight: 1.1 }}>
                            {cat.lessons?.length || 0} lessons
                          </Typography>
                          <Box sx={{ display: 'flex', justifyContent: 'flex-end' }}>
                            <IconButton size="small" onClick={(event) => openCategoryMenu(event, cat)} sx={{ p: 0.2 }}>
                              <MoreVertIcon sx={{ fontSize: 14 }} />
                            </IconButton>
                          </Box>
                        </Stack>
                      </Paper>
                    ))}
                  </Box>
                  {!categories.length && (
                    <Typography variant="body2" color="text.secondary" sx={{ mt: 1 }}>
                      No lesson categories found.
                    </Typography>
                  )}
                </Paper>

                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                  <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1 }}>
                    <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                      Lessons in Selected Category
                    </Typography>
                    <Button
                      size="small"
                      variant="contained"
                      startIcon={<AddIcon />}
                      onClick={openLessonTypeSelector}
                      disabled={!selectedCategory}
                    >
                      Add Lesson
                    </Button>
                  </Stack>
                  {selectedCategory ? (
                    <>
                      <Typography variant="caption" color="text.secondary">
                        Category: {selectedCategory.category_title}
                      </Typography>
                      <TableContainer sx={{ mt: 1 }}>
                        <Table size="small">
                          <TableHead>
                            <TableRow>
                              <TableCell sx={{ fontWeight: 700 }}>Title</TableCell>
                              <TableCell sx={{ fontWeight: 700 }}>Duration</TableCell>
                              <TableCell sx={{ fontWeight: 700 }}>Flags</TableCell>
                              <TableCell align="right" sx={{ fontWeight: 700 }}>Actions</TableCell>
                            </TableRow>
                          </TableHead>
                          <TableBody>
                            {selectedCategory.lessons?.map((lesson) => (
                              <TableRow key={lesson.id}>
                                <TableCell>{lesson.title}</TableCell>
                                <TableCell>{formatDurationHms(lesson.duration)}</TableCell>
                                <TableCell>
                                  <Stack direction="row" spacing={0.5}>
                                    <Chip size="small" label={Number(lesson.isVideo) === 1 ? 'Video' : 'Document'} />
                                    {Number(lesson.isVip) === 1 && <Chip size="small" label="VIP" color="warning" />}
                                  </Stack>
                                </TableCell>
                                <TableCell align="right" sx={{ whiteSpace: 'nowrap' }}>
                                  <IconButton size="small" onClick={(e) => openLessonMenu(e, lesson)} title="Actions">
                                    <MoreVertIcon fontSize="small" />
                                  </IconButton>
                                </TableCell>
                              </TableRow>
                            ))}
                            {!selectedCategory.lessons?.length && (
                              <TableRow>
                                <TableCell colSpan={4}>
                                  <Typography variant="body2" color="text.secondary">
                                    No lessons yet.
                                  </Typography>
                                </TableCell>
                              </TableRow>
                            )}
                          </TableBody>
                        </Table>
                      </TableContainer>
                    </>
                  ) : (
                    <Typography variant="body2" color="text.secondary">
                      Select a category to manage lessons.
                    </Typography>
                  )}
                </Paper>
              </>
            )}
          </Stack>

          <Paper variant="outlined" sx={{ borderRadius: 2, overflow: 'hidden', alignSelf: { xs: 'stretch', md: 'start' }, position: { md: 'sticky' }, top: { md: 72 }, width: { xs: '100%', md: 320 } }}>
            <Box sx={{ p: 2, borderBottom: '1px solid', borderColor: 'divider' }}>
              <Stack direction="row" spacing={1.25} alignItems="center">
                <Avatar
                  src={buildImageUrl(selectedLanguage?.image_path)}
                  sx={{ width: 36, height: 36, bgcolor: selectedLanguage?.primary_color || 'action.selected' }}
                >
                  <LanguageIcon fontSize="small" />
                </Avatar>
                <Box sx={{ minWidth: 0 }}>
                  <Typography variant="subtitle2" sx={{ fontWeight: 700 }} noWrap>
                    {selectedLanguage?.display_name || selectedLanguage?.name || major || 'Channel'}
                  </Typography>
                  <Typography variant="caption" color="text.secondary" noWrap sx={{ display: 'block' }}>
                    {major || '-'}
                  </Typography>
                </Box>
              </Stack>
              <Divider sx={{ my: 1.25 }} />
              <TextField
                size="small"
                select
                fullWidth
                label="Channel / Major"
                value={major}
                onChange={(event) => openWorkspaceFor(event.target.value, null)}
              >
                {languages.map((l) => (
                  <MenuItem key={l.code} value={l.code}>
                    {l.display_name || l.name || l.code}
                  </MenuItem>
                ))}
              </TextField>
              {availableCourses.length > 0 && (
                <TextField
                  size="small"
                  select
                  fullWidth
                  label="Add Course to Channel"
                  value=""
                  onChange={(event) => {
                    const id = Number(event.target.value);
                    if (id) {
                      openWorkspaceFor(major, id);
                    }
                  }}
                  sx={{ mt: 1.25 }}
                >
                  {availableCourses.map((c) => (
                    <MenuItem key={`available-course-${c.course_id}`} value={String(c.course_id)}>
                      {c.title} (ID {c.course_id})
                    </MenuItem>
                  ))}
                </TextField>
              )}
            </Box>
            <List dense sx={{ p: 1 }}>
              {course && !courseInScope && (
                <ListItem disablePadding sx={{ mb: 0.5 }}>
                  <Paper variant="outlined" sx={{ width: '100%', p: 1, borderRadius: 1.25 }}>
                    <Stack direction="row" spacing={1} alignItems="center" justifyContent="space-between">
                      <Box sx={{ minWidth: 0 }}>
                        <Typography variant="body2" sx={{ fontWeight: 700 }} noWrap>
                          {course.title}
                        </Typography>
                        <Typography variant="caption" color="text.secondary" noWrap sx={{ display: 'block' }}>
                          ID {course.course_id}
                        </Typography>
                      </Box>
                      <Chip size="small" label="Not Assigned" variant="outlined" />
                    </Stack>
                  </Paper>
                </ListItem>
              )}
              {courses.map((c) => {
                const selected = Number(course?.course_id || 0) === Number(c.course_id);
                return (
                  <ListItem key={c.course_id} disablePadding sx={{ mb: 0.25 }}>
                    <ListItemButton
                      selected={selected}
                      sx={{ borderRadius: 1 }}
                      onClick={() => openWorkspaceFor(major, c.course_id)}
                    >
                      <ListItemIcon sx={{ minWidth: 34 }}>
                        <SchoolIcon fontSize="small" />
                      </ListItemIcon>
                      <ListItemText
                        primary={c.title}
                        secondary={`ID ${c.course_id}`}
                        primaryTypographyProps={{ fontSize: 13, fontWeight: selected ? 700 : 500, noWrap: true }}
                        secondaryTypographyProps={{ fontSize: 11.5, noWrap: true }}
                      />
                      <OpenInNewIcon fontSize="small" />
                    </ListItemButton>
                  </ListItem>
                );
              })}
              {!courses.length && (
                <Box sx={{ p: 1 }}>
                  <Typography variant="body2" color="text.secondary">
                    No courses available.
                  </Typography>
                </Box>
              )}
            </List>
            {isMobile && <Divider />}
          </Paper>
        </Box>
      </Stack>
      <Snackbar open={Boolean(flash?.success)} autoHideDuration={3000} anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}>
        <Alert severity="success" variant="filled">
          {flash?.success || 'Action completed successfully.'}
        </Alert>
      </Snackbar>

      <Menu
        anchorEl={categoryMenuAnchorEl}
        open={Boolean(categoryMenuAnchorEl)}
        onClose={closeCategoryMenu}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
        transformOrigin={{ vertical: 'top', horizontal: 'right' }}
      >
        <MenuItem
          onClick={() => {
            if (categoryMenuTarget) {
              openEditCategory(categoryMenuTarget);
            }
            closeCategoryMenu();
          }}
        >
          <ListItemIcon sx={{ minWidth: 30 }}>
            <EditIcon fontSize="small" />
          </ListItemIcon>
          <ListItemText primary="Edit Category" />
        </MenuItem>
        <MenuItem
          onClick={() => {
            if (categoryMenuTarget) {
              removeCategory(categoryMenuTarget);
            }
            closeCategoryMenu();
          }}
        >
          <ListItemIcon sx={{ minWidth: 30 }}>
            <DeleteIcon fontSize="small" color="error" />
          </ListItemIcon>
          <ListItemText primary="Delete Category" />
        </MenuItem>
        <MenuItem
          onClick={() => {
            if (categoryMenuTarget && course) {
              if (confirm(`Set all lessons in "${categoryMenuTarget.category_title}" to VIP?`)) {
                router.post(
                  `${admin_app_url}/courses/${course.course_id}/categories/${categoryMenuTarget.id}/lessons/vip-bulk?major=${encodeURIComponent(major)}`,
                  { vip: 1 },
                  { preserveScroll: true }
                );
              }
            }
            closeCategoryMenu();
          }}
        >
          <ListItemIcon sx={{ minWidth: 30 }}>
            <AutoAwesomeIcon fontSize="small" />
          </ListItemIcon>
          <ListItemText primary="Make All Lessons VIP" />
        </MenuItem>
        <MenuItem
          onClick={() => {
            if (categoryMenuTarget && course) {
              if (confirm(`Remove VIP from all lessons in "${categoryMenuTarget.category_title}"?`)) {
                router.post(
                  `${admin_app_url}/courses/${course.course_id}/categories/${categoryMenuTarget.id}/lessons/vip-bulk?major=${encodeURIComponent(major)}`,
                  { vip: 0 },
                  { preserveScroll: true }
                );
              }
            }
            closeCategoryMenu();
          }}
        >
          <ListItemIcon sx={{ minWidth: 30 }}>
            <ConstructionIcon fontSize="small" />
          </ListItemIcon>
          <ListItemText primary="Remove All Lessons VIP" />
        </MenuItem>
      </Menu>

      <Menu
        anchorEl={lessonMenuAnchorEl}
        open={Boolean(lessonMenuAnchorEl)}
        onClose={closeLessonMenu}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
        transformOrigin={{ vertical: 'top', horizontal: 'right' }}
      >
        {Number(lessonMenuTarget?.isVideo || 0) === 1 && lessonMenuTarget && course && selectedCategory && (
          <MenuItem
            onClick={() => {
              const lesson = lessonMenuTarget;
              closeLessonMenu();
              router.visit(
                `${admin_app_url}/courses/${course.course_id}/categories/${selectedCategory.id}/lessons/${lesson.id}/video-detail?major=${encodeURIComponent(major)}`
              );
            }}
          >
            <ListItemIcon sx={{ minWidth: 30 }}>
              <AutoAwesomeIcon fontSize="small" />
            </ListItemIcon>
            <ListItemText primary="Video Detail" />
          </MenuItem>
        )}
        {Number(lessonMenuTarget?.isVideo || 0) !== 1 && Boolean(lessonMenuTarget?.document_link) && lessonMenuTarget && course && selectedCategory && (
          <MenuItem
            onClick={() => {
              const lesson = lessonMenuTarget;
              closeLessonMenu();
              router.visit(
                `${admin_app_url}/courses/${course.course_id}/categories/${selectedCategory.id}/lessons/${lesson.id}/html?major=${encodeURIComponent(major)}`
              );
            }}
          >
            <ListItemIcon sx={{ minWidth: 30 }}>
              <ConstructionIcon fontSize="small" />
            </ListItemIcon>
            <ListItemText primary="Edit Document" />
          </MenuItem>
        )}
        <MenuItem
          onClick={() => {
            const lesson = lessonMenuTarget;
            closeLessonMenu();
            if (lesson) {
              openEditLesson(lesson);
            }
          }}
        >
          <ListItemIcon sx={{ minWidth: 30 }}>
            <EditIcon fontSize="small" />
          </ListItemIcon>
          <ListItemText primary="Edit Lesson" />
        </MenuItem>
        <MenuItem
          onClick={() => {
            const lesson = lessonMenuTarget;
            closeLessonMenu();
            if (lesson) {
              removeLesson(lesson);
            }
          }}
        >
          <ListItemIcon sx={{ minWidth: 30 }}>
            <DeleteIcon fontSize="small" color="error" />
          </ListItemIcon>
          <ListItemText primary="Delete Lesson" />
        </MenuItem>
      </Menu>

      <Dialog open={lessonTypeSelectorOpen} onClose={() => setLessonTypeSelectorOpen(false)} maxWidth="xs" fullWidth>
        <DialogTitle>Select Lesson Type</DialogTitle>
        <DialogContent dividers>
          <Stack spacing={1.25}>
            <Button variant="outlined" onClick={() => openLessonPlaceholder('video')}>
              Video Lesson
            </Button>
            <Button variant="outlined" onClick={() => openLessonPlaceholder('document')}>
              Document Lesson
            </Button>
            <Button variant="outlined" onClick={() => openLessonPlaceholder('document-bulk')}>
              Bulk Document Lessons
            </Button>
          </Stack>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setLessonTypeSelectorOpen(false)}>Cancel</Button>
        </DialogActions>
      </Dialog>

      <Dialog open={createLessonFormOpen} onClose={closeCreateLessonForm} maxWidth="md" fullWidth>
        <DialogTitle>{newLessonType === 'video' ? 'Add Video Lesson' : newLessonType === 'document-bulk' ? 'Add Bulk Document Lessons' : 'Add Document Lesson'}</DialogTitle>
        <Box component="form" onSubmit={handleCreateLessonSubmit}>
          <DialogContent dividers>
            {Boolean(firstLessonErrorMessage) && (
              <Alert severity="error" sx={{ mb: 1.25 }}>
                {firstLessonErrorMessage}
              </Alert>
            )}
            {newLessonType === 'video' ? (
              <VideoLessonCreateForm
                lessonData={lessonData}
                lessonErrors={lessonErrors}
                setLessonData={setLessonData}
                createLessonVideoName={createLessonVideoName}
                handleCreateLessonVideoChange={handleCreateLessonVideoChange}
                createLessonThumbPreview={createLessonThumbPreview}
                createLessonThumbName={createLessonThumbName}
                handleCreateLessonThumbnailChange={handleCreateLessonThumbnailChange}
                lectureNotes={lectureNotes}
                onAddLectureNote={addLectureNote}
                onRemoveLectureNote={removeLectureNoteItem}
                onChangeLectureNote={changeLectureNote}
              />
            ) : newLessonType === 'document-bulk' ? (
              <BulkDocumentLessonCreateForm
                lessonData={lessonData}
                lessonErrors={lessonErrors}
                setLessonData={setLessonData}
                uploadItems={bulkUploadItems}
                uploading={bulkUploading}
                handleBulkDocumentFilesChange={handleBulkDocumentFilesChange}
              />
            ) : (
              <DocumentLessonCreateForm
                lessonData={lessonData}
                lessonErrors={lessonErrors}
                setLessonData={setLessonData}
                documentFileName={createLessonDocumentName}
                handleDocumentLessonFileChange={handleDocumentLessonFileChange}
              />
            )}
          </DialogContent>
          <DialogActions>
            <Button onClick={closeCreateLessonForm} disabled={lessonProcessing || bulkUploading}>Cancel</Button>
            <Button type="submit" variant="contained" disabled={lessonProcessing || bulkUploading}>
              Save Lesson
            </Button>
          </DialogActions>
        </Box>
      </Dialog>

      <Dialog open={categoryDialogOpen} onClose={() => setCategoryDialogOpen(false)} maxWidth="sm" fullWidth>
        <DialogTitle>{editingCategory ? 'Edit Category' : 'Add Category'}</DialogTitle>
        <Box component="form" onSubmit={submitCategory}>
          <DialogContent dividers>
            <Stack spacing={1.5}>
              <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', sm: 'repeat(2, minmax(0,1fr))' }, gap: 1.25 }}>
                <TextField label="Category Key" value={categoryData.category} onChange={(event) => setCategoryData('category', event.target.value)} error={Boolean(categoryErrors.category)} helperText={categoryErrors.category} />
                <TextField label="Category Title" value={categoryData.category_title} onChange={(event) => setCategoryData('category_title', event.target.value)} error={Boolean(categoryErrors.category_title)} helperText={categoryErrors.category_title} />
                <TextField label="Major" value={major} InputProps={{ readOnly: true }} />
                <TextField type="number" label="Sort Order" value={categoryData.sort_order} onChange={(event) => setCategoryData('sort_order', event.target.value)} error={Boolean(categoryErrors.sort_order)} helperText={categoryErrors.sort_order} />
              </Box>

              <Paper variant="outlined" sx={{ p: 1.5, borderRadius: 1.5, borderStyle: 'dashed', borderColor: categoryErrors.category_image ? 'error.main' : 'divider' }}>
                <Stack direction={{ xs: 'column', sm: 'row' }} spacing={1.25} alignItems={{ xs: 'flex-start', sm: 'center' }} justifyContent="space-between">
                  <Stack direction="row" spacing={1.25} alignItems="center">
                    <Avatar
                      src={categoryImagePreview || categoryData.image_url || ''}
                      variant="rounded"
                      sx={{ width: 56, height: 56, bgcolor: 'action.hover' }}
                    >
                      <SchoolIcon sx={{ fontSize: 16 }} />
                    </Avatar>
                    <Box>
                      <Typography variant="caption" sx={{ fontWeight: 700, display: 'block' }}>
                        Category Image
                      </Typography>
                      <Typography variant="caption" color="text.secondary">
                        {categoryImageName || 'No file selected'}
                      </Typography>
                    </Box>
                  </Stack>
                  <Button component="label" size="small" variant="outlined">
                    Upload Image
                    <input hidden type="file" accept="image/*" onChange={handleCategoryImageChange} />
                  </Button>
                </Stack>
                {(categoryErrors.category_image || categoryErrors.image_url) && (
                  <Typography variant="caption" color="error.main" sx={{ mt: 0.75, display: 'block' }}>
                    {categoryErrors.category_image || categoryErrors.image_url}
                  </Typography>
                )}
              </Paper>
            </Stack>
          </DialogContent>
          <DialogActions>
            <Button onClick={() => setCategoryDialogOpen(false)} disabled={categoryProcessing}>Cancel</Button>
            <Button type="submit" variant="contained" disabled={categoryProcessing}>
              {editingCategory ? 'Update' : 'Create'}
            </Button>
          </DialogActions>
        </Box>
      </Dialog>

      <Dialog open={lessonDialogOpen} onClose={() => setLessonDialogOpen(false)} maxWidth="md" fullWidth>
        <DialogTitle>{editingLesson && Number(editingLesson.isVideo || 0) === 1 ? 'Edit Video Lesson' : 'Edit Lesson'}</DialogTitle>
        <Box component="form" onSubmit={submitLesson}>
          <DialogContent dividers>
            {Boolean(firstLessonErrorMessage) && (
              <Alert severity="error" sx={{ mb: 1.25 }}>
                {firstLessonErrorMessage}
              </Alert>
            )}
            {editingLesson && Number(editingLesson.isVideo || 0) === 1 ? (
              <VideoLessonCreateForm
                lessonData={lessonData}
                lessonErrors={lessonErrors}
                setLessonData={setLessonData}
                createLessonVideoName={createLessonVideoName}
                handleCreateLessonVideoChange={handleCreateLessonVideoChange}
                createLessonThumbPreview={createLessonThumbPreview}
                createLessonThumbName={createLessonThumbName}
                handleCreateLessonThumbnailChange={handleCreateLessonThumbnailChange}
                lectureNotes={lectureNotes}
                onAddLectureNote={addLectureNote}
                onRemoveLectureNote={removeLectureNoteItem}
                onChangeLectureNote={changeLectureNote}
              />
            ) : (
              <DocumentLessonCreateForm
                lessonData={lessonData}
                lessonErrors={lessonErrors}
                setLessonData={setLessonData}
                documentFileName={createLessonDocumentName}
                handleDocumentLessonFileChange={handleDocumentLessonFileChange}
              />
            )}
          </DialogContent>
          <DialogActions>
            <Button onClick={() => setLessonDialogOpen(false)} disabled={lessonProcessing}>Cancel</Button>
            <Button type="submit" variant="contained" disabled={lessonProcessing}>
              Update
            </Button>
          </DialogActions>
        </Box>
      </Dialog>
    </Box>
  );
}

AdditionalLessonsWorkspace.layout = (page) => <AdminLayout children={page} />;
