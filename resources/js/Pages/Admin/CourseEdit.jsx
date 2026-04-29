import React, { useEffect, useMemo, useRef, useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import ImageCropper from '../../Components/Admin/ImageCropper';
import VideoLessonCreateForm from './CourseEdit/VideoLessonCreateForm';
import DocumentLessonCreateForm from './CourseEdit/DocumentLessonCreateForm';
import BulkDocumentLessonCreateForm from './CourseEdit/BulkDocumentLessonCreateForm';
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
  Grid,
  Divider,
  IconButton,
  List,
  ListItem,
  ListItemButton,
  ListItemIcon,
  ListItemText,
  LinearProgress,
  Menu,
  MenuItem,
  Paper,
  Stack,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TablePagination,
  TableRow,
  TextField,
  Tooltip,
  Typography,
  useTheme,
  useMediaQuery,
} from '@mui/material';
import { ThemeProvider, createTheme } from '@mui/material/styles';
import {
  Analytics as OverviewIcon,
  AddCircle as CurriculumIcon,
  Star as ReviewsIcon,
  School as StudentsIcon,
  Edit as EditCourseIcon,
  Today as DailyPlanIcon,
  Construction as ConstructionIcon,
  School as SchoolIcon,
  AutoAwesome as AutoAwesomeIcon,
  Add as AddIcon,
  Edit as EditIcon,
  Delete as DeleteIcon,
  MoreVert as MoreVertIcon,
  WorkspacePremium as CertificateIcon,
  FormatBold as FormatBoldIcon,
  FormatItalic as FormatItalicIcon,
  FormatListBulleted as FormatListBulletedIcon,
  FormatListNumbered as FormatListNumberedIcon,
  OpenInNew as OpenInNewIcon,
  Search as SearchIcon,
} from '@mui/icons-material';

const navItems = [
  { key: 'overview', label: 'Overview', icon: <OverviewIcon fontSize="small" /> },
  { key: 'curriculum', label: 'Add Curriculumn', icon: <CurriculumIcon fontSize="small" /> },
  { key: 'daily-plan', label: 'Daily Plan', icon: <DailyPlanIcon fontSize="small" /> },
  { key: 'reviews', label: 'Reviews', icon: <ReviewsIcon fontSize="small" /> },
  { key: 'students', label: 'Student Enrolled', icon: <StudentsIcon fontSize="small" /> },
  { key: 'edit', label: 'Edit Course', icon: <EditCourseIcon fontSize="small" /> },
];

const defaultCategoryForm = {
  category: '',
  category_title: '',
  image_url: '',
  category_image: null,
  sort_order: 0,
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
  major: '',
  notes: '',
  date: 0,
  video_file: null,
  html_file: null,
  html_files: [],
  thumbnail_image: null,
};

const createEmptyLectureNote = () => ({
  hour: '',
  minute: '',
  second: '',
  note: '',
});

export default function CourseEdit({
  course,
  teachers = [],
  majorOptions = [],
  categories = [],
  dailyPlans = [],
  reviews = [],
  reviewStats = { total: 0, average: 0, breakdown: [] },
  enrolledStudents = { data: [], total: 0, per_page: 25, current_page: 1 },
  enrolledStudentsFilters = { q: '', perPage: 25 },
  enrollmentStats = { total: 0, active: 0, deleted: 0 },
}) {
  const { admin_app_url, flash } = usePage().props;
  const theme = useTheme();
  const compactTheme = useMemo(() => createTheme(theme, {
    components: {
      MuiButton: { defaultProps: { size: 'small' } },
      MuiIconButton: { defaultProps: { size: 'small' } },
      MuiTextField: { defaultProps: { size: 'small' } },
      MuiFormControl: { defaultProps: { size: 'small' } },
      MuiChip: { defaultProps: { size: 'small' } },
      MuiTable: { defaultProps: { size: 'small' } },
      MuiTableCell: {
        styleOverrides: {
          root: { paddingTop: 8, paddingBottom: 8 },
          head: { paddingTop: 8, paddingBottom: 8, fontWeight: 700 },
        },
      },
      MuiDialogTitle: {
        styleOverrides: {
          root: { paddingTop: 10, paddingBottom: 10, fontSize: 16, fontWeight: 700 },
        },
      },
      MuiDialogContent: {
        styleOverrides: {
          root: { paddingTop: 12, paddingBottom: 12 },
        },
      },
      MuiDialogActions: {
        styleOverrides: {
          root: { paddingTop: 8, paddingBottom: 8, paddingLeft: 12, paddingRight: 12 },
        },
      },
      MuiListItem: { defaultProps: { dense: true } },
      MuiListItemButton: { defaultProps: { dense: true } },
      MuiMenuItem: { defaultProps: { dense: true } },
    },
  }), [theme]);
  const isMobile = useMediaQuery(theme.breakpoints.down('md'));
  const [activeMenuKey, setActiveMenuKey] = useState('overview');
  const [selectedCategoryId, setSelectedCategoryId] = useState(categories[0]?.id || null);
  const [categoryDialogOpen, setCategoryDialogOpen] = useState(false);
  const [editingCategory, setEditingCategory] = useState(null);
  const [lessonDialogOpen, setLessonDialogOpen] = useState(false);
  const [editingLesson, setEditingLesson] = useState(null);
  const [lessonTypeSelectorOpen, setLessonTypeSelectorOpen] = useState(false);
  const [newLessonType, setNewLessonType] = useState('video');
  const [createLessonFormOpen, setCreateLessonFormOpen] = useState(false);
  const [categoryMenuAnchorEl, setCategoryMenuAnchorEl] = useState(null);
  const [categoryMenuTarget, setCategoryMenuTarget] = useState(null);
  const [curriculumLessonMenuAnchorEl, setCurriculumLessonMenuAnchorEl] = useState(null);
  const [curriculumLessonMenuTarget, setCurriculumLessonMenuTarget] = useState(null);
  const [dailyPlanMenuAnchorEl, setDailyPlanMenuAnchorEl] = useState(null);
  const [dailyPlanMenuTarget, setDailyPlanMenuTarget] = useState(null);
  const [categoryImagePreview, setCategoryImagePreview] = useState('');
  const [categoryImageName, setCategoryImageName] = useState('');
  const [createLessonThumbPreview, setCreateLessonThumbPreview] = useState('');
  const [createLessonThumbName, setCreateLessonThumbName] = useState('');
  const [createLessonVideoName, setCreateLessonVideoName] = useState('');
  const [createLessonDocumentName, setCreateLessonDocumentName] = useState('');
  const [bulkUploadItems, setBulkUploadItems] = useState([]);
  const [bulkUploading, setBulkUploading] = useState(false);
  const [thumbCropperOpen, setThumbCropperOpen] = useState(false);
  const [thumbTempImage, setThumbTempImage] = useState(null);
  const [webCoverCropperOpen, setWebCoverCropperOpen] = useState(false);
  const [webCoverTempImage, setWebCoverTempImage] = useState(null);
  const [courseCoverFileName, setCourseCoverFileName] = useState('');
  const [courseWebCoverFileName, setCourseWebCoverFileName] = useState('');
  const [coursePreviewVideoFileName, setCoursePreviewVideoFileName] = useState('');
  const [coursePreviewVideoPreview, setCoursePreviewVideoPreview] = useState('');
  const [lectureNotes, setLectureNotes] = useState([createEmptyLectureNote()]);
  const courseDetailsRef = useRef(null);
  const courseDetailsDraftRef = useRef('');
  const [courseDetailsFocused, setCourseDetailsFocused] = useState(false);

  const {
    data: categoryData,
    setData: setCategoryData,
    post: postCategory,
    delete: deleteCategory,
    processing: categoryProcessing,
    errors: categoryErrors,
    reset: resetCategory,
    clearErrors: clearCategoryErrors,
  } = useForm(defaultCategoryForm);

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
  } = useForm(defaultLessonForm);

  const {
    data: studyPlanData,
    setData: setStudyPlanData,
    post: postStudyPlan,
    delete: deleteStudyPlanEntry,
    processing: studyPlanProcessing,
    errors: studyPlanErrors,
    reset: resetStudyPlan,
    clearErrors: clearStudyPlanErrors,
  } = useForm({
    day: 1,
    lesson_id: '',
  });
  const {
    data: reviewFormData,
    setData: setReviewFormData,
    post: postReviewForm,
    delete: deleteReviewEntry,
    processing: reviewProcessing,
    errors: reviewErrors,
    reset: resetReviewForm,
    clearErrors: clearReviewErrors,
  } = useForm({
    star: 5,
    review: '',
  });
  const {
    data: courseFormData,
    setData: setCourseFormData,
    post: postCourseForm,
    processing: courseProcessing,
    errors: courseErrors,
    reset: resetCourseForm,
  } = useForm({
    teacher_id: Number(course?.teacher_id || 0),
    title: String(course?.title || ''),
    certificate_title: String(course?.certificate_title || ''),
    lessons_count: Number(course?.lessons_count || 0),
    cover_url: String(course?.cover_url || ''),
    web_cover: String(course?.web_cover || ''),
    cover_image: null,
    web_cover_image: null,
    description: String(course?.description || ''),
    details: String(course?.details || ''),
    is_vip: Number(course?.is_vip || 0) === 1,
    active: Number(course?.active || 0) === 1,
    duration: Number(course?.duration || 0),
    background_color: String(course?.background_color || '#FFFFFF'),
    fee: Number(course?.fee || 0),
    enroll: Number(course?.enroll || 0),
    rating: Number(course?.rating || 0),
    major: String(course?.major || ''),
    sorting: Number(course?.sorting || 0),
    preview: String(course?.preview || ''),
    preview_video: null,
    certificate_code: String(course?.certificate_code || ''),
  });

  const activeMenu = useMemo(
    () => navItems.find((item) => item.key === activeMenuKey) || navItems[0],
    [activeMenuKey]
  );
  const courseTeacherName = useMemo(() => {
    const found = teachers.find((item) => Number(item.id) === Number(course?.teacher_id || 0));
    return found?.name || '';
  }, [teachers, course?.teacher_id]);

  const menuPlaceholderMap = {
    overview: {
      title: 'Overview Placeholder',
      description: 'KPI snapshots, performance trend, completion rate, and quick status cards for this course.',
      blocks: ['Course health', 'Performance chart', 'Engagement trend'],
    },
    curriculum: {
      title: 'Curriculumn Placeholder',
      description: 'Section and lesson builder with reorder controls, publish status, and validation checks.',
      blocks: ['Module list', 'Lesson ordering', 'Publish readiness'],
    },
    'daily-plan': {
      title: 'Daily Plan Placeholder',
      description: 'Day-by-day study structure, learning objective planning, and progression checkpoints.',
      blocks: ['Plan calendar', 'Objectives by day', 'Progress checkpoints'],
    },
    reviews: {
      title: 'Reviews Placeholder',
      description: 'Rating breakdown, sentiment summary, and action queue for instructor responses.',
      blocks: ['Rating summary', 'Review stream', 'Response management'],
    },
    students: {
      title: 'Student Enrolled Placeholder',
      description: 'Enrollment overview, learner list, participation indicators, and completion segments.',
      blocks: ['Enrollment stats', 'Learner roster', 'Completion cohorts'],
    },
    edit: {
      title: 'Edit Course Placeholder',
      description: 'Course metadata and media update workflow with validation and save history.',
      blocks: ['Core metadata', 'Media settings', 'Versioned updates'],
    },
  };

  const currentPlaceholder = menuPlaceholderMap[activeMenu.key];
  const isOverview = activeMenu.key === 'overview';
  const isCurriculum = activeMenu.key === 'curriculum';
  const isDailyPlan = activeMenu.key === 'daily-plan';
  const isReviews = activeMenu.key === 'reviews';
  const isStudents = activeMenu.key === 'students';
  const isEdit = activeMenu.key === 'edit';
  const courseDurationDays = Math.max(1, Number(course?.duration || 1));
  const dayOptions = useMemo(
    () => Array.from({ length: courseDurationDays }, (_, index) => index + 1),
    [courseDurationDays]
  );

  const courseLessons = useMemo(
    () => categories.flatMap((category) =>
      (category.lessons || []).map((lesson) => ({
        ...lesson,
        category_title: category.category_title,
      }))
    ),
    [categories]
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

  const selectedCategory = useMemo(
    () => categories.find((item) => item.id === selectedCategoryId) || null,
    [categories, selectedCategoryId]
  );

  const [editingStudyPlan, setEditingStudyPlan] = useState(null);
  const [planSearchKeyword, setPlanSearchKeyword] = useState('');
  const [planDayFilter, setPlanDayFilter] = useState('all');
  const [reviewSearchKeyword, setReviewSearchKeyword] = useState('');
  const [reviewStarFilter, setReviewStarFilter] = useState('all');
  const [editingReview, setEditingReview] = useState(null);
  const [reviewDialogOpen, setReviewDialogOpen] = useState(false);
  const [studentSearchKeyword, setStudentSearchKeyword] = useState(() => String(enrolledStudentsFilters?.q || ''));

  const flatDailyPlanItems = useMemo(
    () => dailyPlans.flatMap((plan) => (plan.items || []).map((item) => ({ ...item, day: plan.day }))),
    [dailyPlans]
  );
  const dailyPlanStats = useMemo(() => {
    const totalEntries = flatDailyPlanItems.length;
    const totalDays = new Set(flatDailyPlanItems.map((entry) => Number(entry.day))).size;
    const videoCount = flatDailyPlanItems.filter((entry) => Number(entry.isVideo) === 1).length;
    const vipCount = flatDailyPlanItems.filter((entry) => Number(entry.isVip) === 1).length;

    return {
      totalEntries,
      totalDays,
      videoCount,
      documentCount: totalEntries - videoCount,
      vipCount,
    };
  }, [flatDailyPlanItems]);
  const filteredDailyPlanItems = useMemo(() => {
    const normalizedKeyword = String(planSearchKeyword || '').trim().toLowerCase();
    const normalizedDay = planDayFilter === 'all' ? null : Number(planDayFilter);

    return flatDailyPlanItems
      .filter((entry) => {
        if (normalizedDay && Number(entry.day) !== normalizedDay) {
          return false;
        }
        if (!normalizedKeyword) {
          return true;
        }

        const title = String(entry.lesson_title || '').toLowerCase();
        const category = String(entry.category_title || '').toLowerCase();
        return title.includes(normalizedKeyword) || category.includes(normalizedKeyword);
      })
      .sort((a, b) => {
        if (Number(a.day) !== Number(b.day)) {
          return Number(a.day) - Number(b.day);
        }
        return String(a.lesson_title || '').localeCompare(String(b.lesson_title || ''));
      });
  }, [flatDailyPlanItems, planSearchKeyword, planDayFilter]);
  const groupedFilteredDailyPlans = useMemo(() => {
    const grouped = {};

    filteredDailyPlanItems.forEach((entry) => {
      const day = Number(entry.day || 0);
      if (!grouped[day]) {
        grouped[day] = [];
      }
      grouped[day].push(entry);
    });

    return Object.entries(grouped)
      .map(([day, items]) => ({ day: Number(day), items }))
      .sort((a, b) => a.day - b.day);
  }, [filteredDailyPlanItems]);
  const usedLessonIdsInPlan = useMemo(
    () => new Set(flatDailyPlanItems.map((entry) => Number(entry.lesson_id))),
    [flatDailyPlanItems]
  );
  const availableStudyPlanLessons = useMemo(() => {
    const editingLessonId = editingStudyPlan ? Number(editingStudyPlan.lesson_id) : null;
    return courseLessons.filter((lesson) => {
      const lessonId = Number(lesson.id);
      if (editingLessonId && lessonId === editingLessonId) {
        return true;
      }
      return !usedLessonIdsInPlan.has(lessonId);
    });
  }, [courseLessons, usedLessonIdsInPlan, editingStudyPlan]);
  const totalLessonsCount = Math.max(
    Number(course?.lessons_count || 0),
    Number(courseLessons.length || 0),
  );
  const plannedLessonsCount = Number(usedLessonIdsInPlan.size || 0);
  const lessonCoveragePercent = totalLessonsCount > 0
    ? Math.min(100, Math.round((plannedLessonsCount / totalLessonsCount) * 100))
    : 0;
  const averageRatingPercent = Math.max(
    0,
    Math.min(100, Number(((Number(reviewStats?.average || 0) / 5) * 100).toFixed(1))),
  );
  const vipLessonPercent = dailyPlanStats.totalEntries > 0
    ? Math.round((dailyPlanStats.vipCount / dailyPlanStats.totalEntries) * 100)
    : 0;

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
  const formatReviewTime = (timestamp) => {
    const time = Number(timestamp || 0);
    if (!time) {
      return '-';
    }
    return new Date(time).toLocaleString();
  };
  const normalizedReviewBreakdown = useMemo(
    () => [5, 4, 3, 2, 1].map((star) => {
      const found = (reviewStats?.breakdown || []).find((item) => Number(item.star) === star);
      return {
        star,
        count: Number(found?.count || 0),
        percentage: Number(found?.percentage || 0),
      };
    }),
    [reviewStats]
  );
  const filteredReviews = useMemo(() => {
    const keyword = String(reviewSearchKeyword || '').trim().toLowerCase();
    const star = reviewStarFilter === 'all' ? null : Number(reviewStarFilter);

    return reviews.filter((item) => {
      if (star && Number(item.star) !== star) {
        return false;
      }
      if (!keyword) {
        return true;
      }
      const learner = String(item.learner_name || '').toLowerCase();
      const reviewText = String(item.review || '').toLowerCase();
      return learner.includes(keyword) || reviewText.includes(keyword);
    });
  }, [reviews, reviewSearchKeyword, reviewStarFilter]);
  const studentRows = useMemo(() => {
    if (Array.isArray(enrolledStudents?.data)) {
      return enrolledStudents.data;
    }
    if (Array.isArray(enrolledStudents)) {
      return enrolledStudents;
    }
    return [];
  }, [enrolledStudents]);
  const studentsTotal = Number(enrolledStudents?.total ?? studentRows.length);
  const studentsPerPage = Number(enrolledStudents?.per_page ?? enrolledStudentsFilters?.perPage ?? 25);
  const studentsPage = Number(enrolledStudents?.current_page ?? 1);

  useEffect(() => {
    setStudentSearchKeyword(String(enrolledStudentsFilters?.q || ''));
  }, [enrolledStudentsFilters?.q]);

  const submitStudentSearch = () => {
    if (!isStudents) {
      return;
    }
    router.get(
      `${admin_app_url}/courses/${course.course_id}/edit`,
      {
        studentsPage: 1,
        studentsPerPage,
        studentsQ: String(studentSearchKeyword || '').trim(),
      },
      {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['enrolledStudents', 'enrolledStudentsFilters', 'enrollmentStats'],
      }
    );
  };
  useEffect(() => {
    setCourseFormData({
      teacher_id: Number(course?.teacher_id || 0),
      title: String(course?.title || ''),
      certificate_title: String(course?.certificate_title || ''),
      lessons_count: Number(course?.lessons_count || 0),
      cover_url: String(course?.cover_url || ''),
      web_cover: String(course?.web_cover || ''),
      cover_image: null,
      web_cover_image: null,
      description: String(course?.description || ''),
      details: String(course?.details || ''),
      is_vip: Number(course?.is_vip || 0) === 1,
      active: Number(course?.active || 0) === 1,
      duration: Number(course?.duration || 0),
      background_color: String(course?.background_color || '#FFFFFF'),
      fee: Number(course?.fee || 0),
      enroll: Number(course?.enroll || 0),
      rating: Number(course?.rating || 0),
      major: String(course?.major || ''),
      sorting: Number(course?.sorting || 0),
      preview: String(course?.preview || ''),
      preview_video: null,
      certificate_code: String(course?.certificate_code || ''),
    });
    setCoursePreviewVideoFileName('');
    setCoursePreviewVideoPreview('');
  }, [course, setCourseFormData]);
  useEffect(() => () => {
    if (coursePreviewVideoPreview) {
      URL.revokeObjectURL(coursePreviewVideoPreview);
    }
  }, [coursePreviewVideoPreview]);
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

  const openCreateCategory = () => {
    setEditingCategory(null);
    clearCategoryErrors();
    resetCategory();
    setCategoryData({ ...defaultCategoryForm });
    setCategoryImagePreview('');
    setCategoryImageName('');
    setCategoryDialogOpen(true);
  };

  const openEditCategory = (category) => {
    setEditingCategory(category);
    clearCategoryErrors();
    setCategoryData({
      category: category.category || '',
      category_title: category.category_title || '',
      image_url: category.image_url || '',
      category_image: null,
      sort_order: Number(category.sort_order || 0),
    });
    setCategoryImagePreview(category.image_url || '');
    setCategoryImageName(category.image_url ? String(category.image_url).split('/').pop() : '');
    setCategoryDialogOpen(true);
  };

  const submitCategory = (event) => {
    event.preventDefault();
    const payload = {
      ...categoryData,
      sort_order: Number(categoryData.sort_order || 0),
    };

    if (editingCategory) {
      postCategory(`${admin_app_url}/courses/${course.course_id}/categories/${editingCategory.id}`, {
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

    postCategory(`${admin_app_url}/courses/${course.course_id}/categories`, {
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

  const removeCategory = (category) => {
    if (!confirm(`Delete category "${category.category_title}" and its lessons?`)) {
      return;
    }
    deleteCategory(`${admin_app_url}/courses/${course.course_id}/categories/${category.id}`);
  };

  const openCategoryMenu = (event, category) => {
    event.stopPropagation();
    setCategoryMenuAnchorEl(event.currentTarget);
    setCategoryMenuTarget(category);
  };

  const closeCategoryMenu = () => {
    setCategoryMenuAnchorEl(null);
    setCategoryMenuTarget(null);
  };

  const openCurriculumLessonMenu = (event, lesson) => {
    event.stopPropagation();
    setCurriculumLessonMenuAnchorEl(event.currentTarget);
    setCurriculumLessonMenuTarget(lesson);
  };

  const closeCurriculumLessonMenu = () => {
    setCurriculumLessonMenuAnchorEl(null);
    setCurriculumLessonMenuTarget(null);
  };

  const openDailyPlanMenu = (event, entry) => {
    event.stopPropagation();
    setDailyPlanMenuAnchorEl(event.currentTarget);
    setDailyPlanMenuTarget(entry);
  };

  const closeDailyPlanMenu = () => {
    setDailyPlanMenuAnchorEl(null);
    setDailyPlanMenuTarget(null);
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
      title: lesson.title || '',
      title_mini: lesson.title_mini || '',
      duration: Number(lesson.duration || 0),
      isVip: Number(lesson.isVip || 0) === 1,
      isVideo: isVideoLesson,
      link: lesson.link || '',
      document_link: lesson.document_link || '',
      download_url: lesson.download_url || '',
      thumbnail: lesson.thumbnail || '',
      major: lesson.major || '',
      notes: lesson.notes || '',
      date: Number(lesson.date || 0),
      video_file: null,
      html_file: null,
      html_files: [],
      thumbnail_image: null,
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
    if (!selectedCategory || !editingLesson) {
      return;
    }

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
      };
 
      if (lessonData.video_file) {
        payload.video_file = lessonData.video_file;
      }
      if (lessonData.thumbnail_image) {
        payload.thumbnail_image = lessonData.thumbnail_image;
      }

      transformLesson(() => ({ ...payload, _method: 'patch' }));
      postLesson(`${admin_app_url}/courses/${course.course_id}/categories/${selectedCategory.id}/lessons/${editingLesson.id}`, {
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
    };
    if (lessonData.html_file) {
      payload.html_file = lessonData.html_file;
    }
    transformLesson(() => ({ ...payload, _method: 'patch' }));
    postLesson(`${admin_app_url}/courses/${course.course_id}/categories/${selectedCategory.id}/lessons/${editingLesson.id}`, {
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
    if (!selectedCategory) {
      return;
    }
    if (!confirm(`Delete lesson "${lesson.title}"?`)) {
      return;
    }
    deleteLesson(`${admin_app_url}/courses/${course.course_id}/categories/${selectedCategory.id}/lessons/${lesson.id}`);
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
      major: course?.major || '',
      document_link: '',
      html_file: null,
      html_files: [],
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
    setThumbCropperOpen(false);
    setThumbTempImage(null);
    setLectureNotes([createEmptyLectureNote()]);
    resetLesson();
  };

  const addLectureNote = () => {
    setLectureNotes((prev) => [...prev, createEmptyLectureNote()]);
  };

  const removeLectureNote = (index) => {
    setLectureNotes((prev) => prev.filter((_, itemIndex) => itemIndex !== index));
  };

  const changeLectureNote = (index, key, value) => {
    setLectureNotes((prev) =>
      prev.map((entry, itemIndex) =>
        itemIndex === index
          ? { ...entry, [key]: value }
          : entry
      )
    );
  };

  const handleCreateLessonSubmit = async (event) => {
    event.preventDefault();
    if (!selectedCategory) {
      return;
    }

    if (newLessonType === 'video') {
      const payload = {
        lesson_type: newLessonType,
        title: lessonData.title,
        title_mini: lessonData.title_mini,
        isVip: Boolean(lessonData.isVip),
        isVideo: true,
      };
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

      payload.duration = Number(lessonData.duration || 0);
      payload.date = Date.now();
      payload.major = course?.major || '';
      payload.notes = JSON.stringify(serializedNotes);
      payload.video_file = lessonData.video_file;
      payload.thumbnail_image = lessonData.thumbnail_image;
      postLesson(`${admin_app_url}/courses/${course.course_id}/categories/${selectedCategory.id}/lessons`, {
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

      const endpoint = `${admin_app_url}/courses/${course.course_id}/categories/${selectedCategory.id}/lessons`;
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
      };
      payload.html_file = lessonData.html_file;
      postLesson(`${admin_app_url}/courses/${course.course_id}/categories/${selectedCategory.id}/lessons`, {
        forceFormData: true,
        data: payload,
        onSuccess: () => {
          closeCreateLessonForm();
        },
      });
    }
  };

  const handleCreateLessonVideoChange = (event) => {
    const file = event.target.files?.[0] || null;
    setLessonData('video_file', file);
    setCreateLessonVideoName(file ? file.name : '');
    if (!file) {
      setLessonData('duration', 0);
      return;
    }

    const videoElement = document.createElement('video');
    videoElement.preload = 'metadata';
    const objectUrl = URL.createObjectURL(file);
    videoElement.src = objectUrl;
    videoElement.onloadedmetadata = () => {
      const durationInSeconds = Number.isFinite(videoElement.duration) ? Math.round(videoElement.duration) : 0;
      setLessonData('duration', durationInSeconds > 0 ? durationInSeconds : 0);
      URL.revokeObjectURL(objectUrl);
    };
    videoElement.onerror = () => {
      setLessonData('duration', 0);
      URL.revokeObjectURL(objectUrl);
    };
  };

  const handleDocumentLessonFileChange = (event) => {
    const file = event.target.files?.[0] || null;
    setLessonData('html_file', file);
    setCreateLessonDocumentName(file ? file.name : '');
  };

  const handleBulkDocumentFilesChange = (event) => {
    const files = Array.from(event.target.files || []);
    setLessonData('html_files', files);
    setBulkUploadItems(files.map((file) => ({
      name: file.name,
      progress: 0,
      status: 'pending',
      error: '',
    })));
  };

  const handleCreateLessonThumbnailChange = (event) => {
    const file = event.target.files?.[0];
    if (!file) {
      return;
    }

    const reader = new FileReader();
    reader.onload = () => {
      setThumbTempImage(reader.result);
      setThumbCropperOpen(true);
    };
    reader.readAsDataURL(file);
  };

  const handleCreateLessonThumbCropComplete = (blob) => {
    const file = new File([blob], `thumb_${Date.now()}.jpg`, { type: 'image/jpeg' });
    setLessonData('thumbnail_image', file);
    setCreateLessonThumbName(file.name);
    setCreateLessonThumbPreview(URL.createObjectURL(blob));
    setThumbCropperOpen(false);
    setThumbTempImage(null);
  };

  const openCreateStudyPlan = () => {
    setEditingStudyPlan(null);
    clearStudyPlanErrors();
    resetStudyPlan();
    setStudyPlanData({
      day: 1,
      lesson_id: '',
    });
  };

  const openEditStudyPlan = (entry) => {
    setEditingStudyPlan(entry);
    clearStudyPlanErrors();
    const entryDay = Number(entry.day || 1);
    setStudyPlanData({
      day: Math.min(courseDurationDays, Math.max(1, entryDay)),
      lesson_id: String(entry.lesson_id || ''),
    });
  };

  const submitStudyPlan = (event) => {
    event.preventDefault();
    const payload = {
      day: Number(studyPlanData.day || 1),
      lesson_id: Number(studyPlanData.lesson_id || 0),
    };

    if (!payload.lesson_id) {
      return;
    }

    if (editingStudyPlan) {
      postStudyPlan(`${admin_app_url}/courses/${course.course_id}/study-plan/${editingStudyPlan.id}`, {
        data: { ...payload, _method: 'patch' },
        onSuccess: () => {
          openCreateStudyPlan();
        },
      });
      return;
    }

    postStudyPlan(`${admin_app_url}/courses/${course.course_id}/study-plan`, {
      data: payload,
      onSuccess: () => {
        openCreateStudyPlan();
      },
    });
  };

  const removeStudyPlan = (entry) => {
    if (!confirm(`Remove "${entry.lesson_title}" from day ${entry.day}?`)) {
      return;
    }

    deleteStudyPlanEntry(`${admin_app_url}/courses/${course.course_id}/study-plan/${entry.id}`);
  };
  const openEditReview = (review) => {
    setEditingReview(review);
    clearReviewErrors();
    setReviewFormData({
      star: Number(review.star || 5),
      review: String(review.review || ''),
    });
    setReviewDialogOpen(true);
  };

  const closeReviewDialog = () => {
    setReviewDialogOpen(false);
    setEditingReview(null);
    clearReviewErrors();
    resetReviewForm();
    setReviewFormData({
      star: 5,
      review: '',
    });
  };

  const submitReviewForm = (event) => {
    event.preventDefault();
    if (!editingReview) {
      return;
    }

    postReviewForm(`${admin_app_url}/courses/${course.course_id}/reviews/${editingReview.id}`, {
      data: {
        _method: 'patch',
        star: Number(reviewFormData.star || 5),
        review: String(reviewFormData.review || ''),
      },
      onSuccess: () => {
        closeReviewDialog();
      },
    });
  };

  const removeReview = (review) => {
    if (!confirm(`Delete review from "${review.learner_name || review.user_id}"?`)) {
      return;
    }

    deleteReviewEntry(`${admin_app_url}/courses/${course.course_id}/reviews/${review.id}`);
  };
  const submitCourseForm = (event) => {
    event.preventDefault();
    const detailsFromDom = courseDetailsRef.current ? String(courseDetailsRef.current.innerHTML || '') : '';
    const normalizedDetails = (() => {
      const raw = (detailsFromDom || String(courseFormData.details || '')).trim();
      if (raw === '' || raw === '<br>' || raw === '<p><br></p>' || raw === '<div><br></div>') {
        return '<p></p>';
      }
      return raw;
    })();
    postCourseForm(`${admin_app_url}/courses/${course.course_id}`, {
      forceFormData: true,
      data: {
        _method: 'patch',
        teacher_id: Number(courseFormData.teacher_id || 0),
        title: String(courseFormData.title || ''),
        certificate_title: String(courseFormData.certificate_title || ''),
        lessons_count: Number(courseFormData.lessons_count || 0),
        cover_url: String(courseFormData.cover_url || ''),
        web_cover: String(courseFormData.web_cover || ''),
        cover_image: courseFormData.cover_image,
        web_cover_image: courseFormData.web_cover_image,
        description: String(courseFormData.description || ''),
        details: normalizedDetails,
        is_vip: Boolean(courseFormData.is_vip),
        active: Boolean(courseFormData.active),
        duration: Number(courseFormData.duration || 0),
        background_color: String(courseFormData.background_color || ''),
        fee: Number(courseFormData.fee || 0),
        enroll: Number(courseFormData.enroll || 0),
        rating: Number(courseFormData.rating || 0),
        major: String(courseFormData.major || ''),
        sorting: Number(courseFormData.sorting || 0),
        preview: String(courseFormData.preview || ''),
        preview_video: courseFormData.preview_video,
        certificate_code: String(courseFormData.certificate_code || ''),
      },
    });
  };
  const handleCourseCoverChange = (event) => {
    const file = event.target.files?.[0] || null;
    setCourseFormData('cover_image', file);
    setCourseCoverFileName(file ? file.name : '');
  };
  const handleCourseWebCoverChange = (event) => {
    const file = event.target.files?.[0];
    if (!file) {
      return;
    }
    const reader = new FileReader();
    reader.onload = () => {
      setWebCoverTempImage(reader.result);
      setWebCoverCropperOpen(true);
    };
    reader.readAsDataURL(file);
  };
  const handleCourseWebCoverCropComplete = (blob) => {
    const file = new File([blob], `web_cover_${Date.now()}.jpg`, { type: 'image/jpeg' });
    setCourseFormData('web_cover_image', file);
    setCourseWebCoverFileName(file.name);
    setCourseFormData('web_cover', '');
    setWebCoverCropperOpen(false);
    setWebCoverTempImage(null);
  };
  const handleCoursePreviewVideoChange = (event) => {
    const file = event.target.files?.[0] || null;
    if (coursePreviewVideoPreview) {
      URL.revokeObjectURL(coursePreviewVideoPreview);
    }
    setCourseFormData('preview_video', file);
    setCoursePreviewVideoFileName(file ? file.name : '');
    if (file) {
      const previewUrl = URL.createObjectURL(file);
      setCoursePreviewVideoPreview(previewUrl);
      setCourseFormData('preview', '');
    } else {
      setCoursePreviewVideoPreview('');
    }
  };
  const videoPreviewEmbedUrl = useMemo(() => {
    const raw = String(courseFormData.preview || '').trim();
    if (!raw) {
      return '';
    }
    if (raw.includes('player.vimeo.com/video/')) {
      return raw;
    }
    const match = raw.match(/vimeo\.com\/(?:video\/)?(\d+)/i) || raw.match(/\/videos\/(\d+)/i) || raw.match(/^(\d+)$/);
    if (!match) {
      return '';
    }
    return `https://player.vimeo.com/video/${match[1]}`;
  }, [courseFormData.preview]);
  const directVideoPreviewUrl = useMemo(() => {
    if (coursePreviewVideoPreview) {
      return coursePreviewVideoPreview;
    }
    const raw = String(courseFormData.preview || '').trim();
    if (!raw || videoPreviewEmbedUrl) {
      return '';
    }
    return raw;
  }, [coursePreviewVideoPreview, courseFormData.preview, videoPreviewEmbedUrl]);
  const runDetailsCommand = (command) => {
    if (!courseDetailsRef.current) {
      return;
    }
    courseDetailsRef.current.focus();
    document.execCommand(command, false, null);
    courseDetailsDraftRef.current = String(courseDetailsRef.current.innerHTML || '');
  };
  const formatDetailsBold = () => runDetailsCommand('bold');
  const formatDetailsItalic = () => runDetailsCommand('italic');
  const formatDetailsList = (ordered) => runDetailsCommand(ordered ? 'insertOrderedList' : 'insertUnorderedList');

  useEffect(() => {
    if (!isEdit) {
      return;
    }
    const el = courseDetailsRef.current;
    if (!el || courseDetailsFocused) {
      return;
    }
    const nextHtml = String(courseFormData.details || '') || '<p></p>';
    if (el.innerHTML !== nextHtml) {
      el.innerHTML = nextHtml;
      courseDetailsDraftRef.current = nextHtml;
    }
  }, [isEdit, courseFormData.details, courseDetailsFocused]);

  return (
    <ThemeProvider theme={compactTheme}>
      <Box>
      <Head title="Course Edit Workspace" />
      <Stack spacing={2}>
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: { xs: 'flex-start', md: 'center' }, flexDirection: { xs: 'column', md: 'row' }, gap: 1 }}>
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              Course Edit Workspace
            </Typography>
            <Typography variant="body2" color="text.secondary">
              {course ? `Course: ${course.title}` : 'Placeholder page aligned with instructor-course management layout plan.'}
            </Typography>
          </Box>
          <Button
            variant="contained"
            component={Link}
            href={`${admin_app_url}/courses/create`}
            startIcon={<AddIcon />}
          >
            Create Course
          </Button>
        </Box>

        <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: 'minmax(0,1fr) 260px' }, gap: 2 }}>
          <Stack spacing={2}>
            {isOverview ? (
              <>
                <Grid container spacing={1.5}>
                  <Grid item xs={12} sm={6} md={3}>
                    <Paper variant="outlined" sx={{ p: 1.5, borderRadius: 2 }}>
                      <Typography variant="caption" color="text.secondary">Active Enrolled</Typography>
                      <Typography variant="h6" sx={{ fontWeight: 700 }}>{Number(enrollmentStats?.active || 0)}</Typography>
                      <Typography variant="caption" color="text.secondary">
                        Total {Number(enrollmentStats?.total || 0)} • Deleted {Number(enrollmentStats?.deleted || 0)}
                      </Typography>
                    </Paper>
                  </Grid>
                  <Grid item xs={12} sm={6} md={3}>
                    <Paper variant="outlined" sx={{ p: 1.5, borderRadius: 2 }}>
                      <Typography variant="caption" color="text.secondary">Average Rating</Typography>
                      <Typography variant="h6" sx={{ fontWeight: 700 }}>{Number(reviewStats?.average || 0).toFixed(1)}</Typography>
                      <Typography variant="caption" color="text.secondary">
                        {Number(reviewStats?.total || 0)} reviews
                      </Typography>
                    </Paper>
                  </Grid>
                  <Grid item xs={12} sm={6} md={3}>
                    <Paper variant="outlined" sx={{ p: 1.5, borderRadius: 2 }}>
                      <Typography variant="caption" color="text.secondary">Planned Lessons</Typography>
                      <Typography variant="h6" sx={{ fontWeight: 700 }}>{plannedLessonsCount}</Typography>
                      <Typography variant="caption" color="text.secondary">
                        of {totalLessonsCount} total lessons
                      </Typography>
                    </Paper>
                  </Grid>
                  <Grid item xs={12} sm={6} md={3}>
                    <Paper variant="outlined" sx={{ p: 1.5, borderRadius: 2 }}>
                      <Typography variant="caption" color="text.secondary">Daily Plan Days</Typography>
                      <Typography variant="h6" sx={{ fontWeight: 700 }}>{Number(dailyPlanStats.totalDays || 0)}</Typography>
                      <Typography variant="caption" color="text.secondary">
                        Duration {Number(course?.duration || 0)} days
                      </Typography>
                    </Paper>
                  </Grid>
                </Grid>

                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                  <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1 }}>
                    <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>Course Summary</Typography>
                    <Stack direction="row" spacing={0.75}>
                      <Chip size="small" label={Number(course?.active) === 1 ? 'Active' : 'Inactive'} color={Number(course?.active) === 1 ? 'success' : 'default'} />
                      {Number(course?.is_vip) === 1 && <Chip size="small" label="VIP" color="warning" />}
                      <Chip size="small" label={`Fee ${Number(course?.fee || 0)}`} />
                    </Stack>
                  </Stack>
                  <Stack spacing={0.75}>
                    <Typography variant="body2"><strong>Title:</strong> {course?.title || '-'}</Typography>
                    <Typography variant="body2"><strong>Teacher:</strong> {course?.teacher?.name || `#${course?.teacher_id || '-'}`}</Typography>
                    <Typography variant="body2"><strong>Major:</strong> {course?.major || '-'}</Typography>
                    <Typography variant="body2"><strong>Duration:</strong> {Number(course?.duration || 0)} days</Typography>
                    <Typography variant="body2"><strong>Certificate:</strong> {course?.certificate_title || '-'}</Typography>
                    <Typography variant="body2" color="text.secondary">{course?.description || 'No description provided.'}</Typography>
                  </Stack>
                </Paper>

                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                  <Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 1.5 }}>Coverage & Health</Typography>
                  <Stack spacing={1.25}>
                    <Box>
                      <Stack direction="row" justifyContent="space-between">
                        <Typography variant="caption" color="text.secondary">Lesson Coverage</Typography>
                        <Typography variant="caption" sx={{ fontWeight: 700 }}>{lessonCoveragePercent}%</Typography>
                      </Stack>
                      <LinearProgress variant="determinate" value={lessonCoveragePercent} sx={{ mt: 0.5 }} />
                    </Box>
                    <Box>
                      <Stack direction="row" justifyContent="space-between">
                        <Typography variant="caption" color="text.secondary">Review Score Health</Typography>
                        <Typography variant="caption" sx={{ fontWeight: 700 }}>{averageRatingPercent}%</Typography>
                      </Stack>
                      <LinearProgress variant="determinate" value={averageRatingPercent} sx={{ mt: 0.5 }} />
                    </Box>
                    <Box>
                      <Stack direction="row" justifyContent="space-between">
                        <Typography variant="caption" color="text.secondary">VIP Lessons in Plan</Typography>
                        <Typography variant="caption" sx={{ fontWeight: 700 }}>{vipLessonPercent}%</Typography>
                      </Stack>
                      <LinearProgress variant="determinate" value={vipLessonPercent} sx={{ mt: 0.5 }} />
                    </Box>
                  </Stack>
                </Paper>
              </>
            ) : isCurriculum ? (
              <>
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
                    {categories.map((category) => (
                      <Paper
                        key={category.id}
                        variant={selectedCategoryId === category.id ? 'elevation' : 'outlined'}
                        elevation={selectedCategoryId === category.id ? 2 : 0}
                        sx={{
                          p: 0.75,
                          borderRadius: 1.25,
                          cursor: 'pointer',
                          borderColor: selectedCategoryId === category.id ? 'primary.main' : 'divider',
                        }}
                        onClick={() => setSelectedCategoryId(category.id)}
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
                            {category.image_url ? (
                              <Box
                                component="img"
                                src={category.image_url}
                                alt={category.category_title}
                                sx={{ width: '100%', height: '100%', objectFit: 'cover' }}
                              />
                            ) : (
                              <SchoolIcon color="action" sx={{ fontSize: 14 }} />
                            )}
                          </Box>
                          <Typography variant="caption" sx={{ fontWeight: 700, lineHeight: 1.2 }} noWrap>
                            {category.category_title}
                          </Typography>
                          <Typography variant="caption" color="text.secondary" noWrap sx={{ fontSize: 10.5, lineHeight: 1.1 }}>
                            {category.lessons?.length || 0} lessons
                          </Typography>
                          <Box sx={{ display: 'flex', justifyContent: 'flex-end' }}>
                            <IconButton size="small" onClick={(event) => openCategoryMenu(event, category)} sx={{ p: 0.2 }}>
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
                                  <IconButton size="small" onClick={(e) => openCurriculumLessonMenu(e, lesson)} title="Actions" sx={{ p: 0.5 }}>
                                    <MoreVertIcon fontSize="small" />
                                  </IconButton>
                                </TableCell>
                              </TableRow>
                            ))}
                            {!selectedCategory.lessons?.length && (
                              <TableRow>
                                <TableCell colSpan={4}>
                                  <Typography variant="body2" color="text.secondary">
                                    No lessons yet. Lesson creation will be implemented in next step.
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
            ) : isDailyPlan ? (
              <>
                {Boolean(flash?.success) && (
                  <Alert severity="success">{flash.success}</Alert>
                )}
                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                  <Stack spacing={0.5} sx={{ mb: 2 }}>
                    <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                      Daily Plan Management
                    </Typography>
                    <Typography variant="body2" color="text.secondary">
                      Plan lesson flow by day, keep pacing balanced, and review coverage at a glance.
                    </Typography>
                  </Stack>
                  <Grid container spacing={1.25}>
                    <Grid item xs={12} sm={6} md={2.4}>
                      <Paper variant="outlined" sx={{ p: 1.25, borderRadius: 1.5, bgcolor: 'background.paper' }}>
                        <Typography variant="caption" color="text.secondary">Entries</Typography>
                        <Typography variant="h6" sx={{ fontWeight: 700 }}>{dailyPlanStats.totalEntries}</Typography>
                      </Paper>
                    </Grid>
                    <Grid item xs={12} sm={6} md={2.4}>
                      <Paper variant="outlined" sx={{ p: 1.25, borderRadius: 1.5, bgcolor: 'background.paper' }}>
                        <Typography variant="caption" color="text.secondary">Planned Days</Typography>
                        <Typography variant="h6" sx={{ fontWeight: 700 }}>{dailyPlanStats.totalDays}</Typography>
                      </Paper>
                    </Grid>
                    <Grid item xs={12} sm={6} md={2.4}>
                      <Paper variant="outlined" sx={{ p: 1.25, borderRadius: 1.5, bgcolor: 'background.paper' }}>
                        <Typography variant="caption" color="text.secondary">Video Lessons</Typography>
                        <Typography variant="h6" sx={{ fontWeight: 700 }}>{dailyPlanStats.videoCount}</Typography>
                      </Paper>
                    </Grid>
                    <Grid item xs={12} sm={6} md={2.4}>
                      <Paper variant="outlined" sx={{ p: 1.25, borderRadius: 1.5, bgcolor: 'background.paper' }}>
                        <Typography variant="caption" color="text.secondary">Document Lessons</Typography>
                        <Typography variant="h6" sx={{ fontWeight: 700 }}>{dailyPlanStats.documentCount}</Typography>
                      </Paper>
                    </Grid>
                    <Grid item xs={12} sm={6} md={2.4}>
                      <Paper variant="outlined" sx={{ p: 1.25, borderRadius: 1.5, bgcolor: 'background.paper' }}>
                        <Typography variant="caption" color="text.secondary">VIP Lessons</Typography>
                        <Typography variant="h6" sx={{ fontWeight: 700 }}>{dailyPlanStats.vipCount}</Typography>
                      </Paper>
                    </Grid>
                  </Grid>
                </Paper>

                <Paper variant="outlined" sx={{ p: 1.5, borderRadius: 2 }}>
                  <Stack direction={{ xs: 'column', md: 'row' }} justifyContent="space-between" alignItems={{ xs: 'flex-start', md: 'center' }} spacing={1} sx={{ mb: 1 }}>
                    <Stack direction="row" spacing={1} alignItems="center">
                      <Typography variant="subtitle2" sx={{ fontWeight: 700 }}>
                        {editingStudyPlan ? `Editing Entry #${editingStudyPlan.id}` : 'Create New Entry'}
                      </Typography>
                      {editingStudyPlan && <Chip size="small" color="primary" label={`Day ${editingStudyPlan.day}`} />}
                    </Stack>
                    <Button size="small" variant="outlined" onClick={openCreateStudyPlan}>
                      {editingStudyPlan ? 'Cancel Edit' : 'Reset Form'}
                    </Button>
                  </Stack>
                  <Box component="form" onSubmit={submitStudyPlan} sx={{ display: 'grid', gap: 0.75, gridTemplateColumns: { xs: '1fr', md: '96px minmax(0,1fr) auto' }, alignItems: 'start' }}>
                    <TextField
                      size="small"
                      select
                      label="Day"
                      value={String(studyPlanData.day)}
                      onChange={(event) => setStudyPlanData('day', Number(event.target.value))}
                      error={Boolean(studyPlanErrors.day)}
                      helperText={studyPlanErrors.day || `1 to ${courseDurationDays}`}
                      FormHelperTextProps={{ sx: { mt: 0.25, lineHeight: 1.2 } }}
                    >
                      {dayOptions.map((day) => (
                        <MenuItem key={`day-option-${day}`} value={String(day)}>
                          Day {day}
                        </MenuItem>
                      ))}
                    </TextField>
                    <TextField
                      size="small"
                      select
                      label="Lesson"
                      value={studyPlanData.lesson_id}
                      onChange={(event) => setStudyPlanData('lesson_id', event.target.value)}
                      error={Boolean(studyPlanErrors.lesson_id)}
                      helperText={studyPlanErrors.lesson_id || (!courseLessons.length ? 'Create lessons first in Curriculum.' : !availableStudyPlanLessons.length ? 'All lessons are already assigned to daily plan.' : 'Select a lesson for this day')}
                      FormHelperTextProps={{ sx: { mt: 0.25, lineHeight: 1.2 } }}
                    >
                      {availableStudyPlanLessons.map((lesson) => (
                        <MenuItem key={lesson.id} value={String(lesson.id)}>
                          {lesson.title} ({lesson.category_title || 'No category'})
                        </MenuItem>
                      ))}
                    </TextField>
                    <Button
                      size="small"
                      type="submit"
                      variant="contained"
                      disabled={studyPlanProcessing || !courseLessons.length || !availableStudyPlanLessons.length}
                      sx={{ height: 40, px: 1.25, minWidth: 0, width: 'fit-content', justifySelf: { xs: 'flex-start', md: 'start' }, alignSelf: 'start' }}
                    >
                      {editingStudyPlan ? 'Update Entry' : 'Add Entry'}
                    </Button>
                  </Box>
                </Paper>

                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                  <Stack
                    direction={{ xs: 'column', md: 'row' }}
                    justifyContent="space-between"
                    alignItems={{ xs: 'stretch', md: 'center' }}
                    spacing={1.25}
                    sx={{ mb: 1.5 }}
                  >
                    <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                      Plan Timeline
                    </Typography>
                    <Stack direction={{ xs: 'column', sm: 'row' }} spacing={1}>
                      <TextField
                        size="small"
                        label="Filter Day"
                        select
                        value={planDayFilter}
                        onChange={(event) => setPlanDayFilter(event.target.value)}
                        sx={{ minWidth: 120 }}
                      >
                        <MenuItem value="all">All Days</MenuItem>
                        {[...new Set(flatDailyPlanItems.map((entry) => Number(entry.day || 0)))].sort((a, b) => a - b).map((day) => (
                          <MenuItem key={`filter-day-${day}`} value={String(day)}>
                            Day {day}
                          </MenuItem>
                        ))}
                      </TextField>
                      <TextField
                        size="small"
                        label="Search Lesson"
                        value={planSearchKeyword}
                        onChange={(event) => setPlanSearchKeyword(event.target.value)}
                        sx={{ minWidth: 220 }}
                      />
                    </Stack>
                  </Stack>
                  {groupedFilteredDailyPlans.length ? (
                    <Stack spacing={1.25}>
                      {groupedFilteredDailyPlans.map((group) => (
                        <Paper key={`day-group-${group.day}`} variant="outlined" sx={{ p: 1.25, borderRadius: 1.5 }}>
                          <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 0.75 }}>
                            <Typography variant="subtitle2" sx={{ fontWeight: 700 }}>
                              Day {group.day}
                            </Typography>
                            <Chip size="small" label={`${group.items.length} lesson${group.items.length > 1 ? 's' : ''}`} />
                          </Stack>
                          <Stack spacing={0.75}>
                            {group.items.map((entry) => (
                              <Box
                                key={entry.id}
                                sx={{
                                  border: '1px solid',
                                  borderColor: 'divider',
                                  borderRadius: 1.25,
                                  p: 1,
                                  display: 'grid',
                                  gap: 0.75,
                                  gridTemplateColumns: { xs: '1fr', md: 'minmax(0,1fr) auto' },
                                  alignItems: 'center',
                                }}
                              >
                                <Box>
                                  <Typography variant="body2" sx={{ fontWeight: 700 }}>
                                    {entry.lesson_title}
                                  </Typography>
                                  <Stack direction="row" spacing={0.75} sx={{ mt: 0.5 }} flexWrap="wrap">
                                    <Chip size="small" variant="outlined" label={entry.category_title || 'Uncategorized'} />
                                    <Chip size="small" label={Number(entry.isVideo) === 1 ? 'Video' : 'Document'} />
                                    {Number(entry.isVip) === 1 && <Chip size="small" label="VIP" color="warning" />}
                                    <Chip size="small" variant="outlined" label={formatDurationHms(entry.duration)} />
                                  </Stack>
                                </Box>
                                <Stack direction="row" spacing={0.5} justifyContent="flex-end">
                                  <IconButton size="small" onClick={(e) => openDailyPlanMenu(e, entry)} title="Actions" sx={{ p: 0.5 }}>
                                    <MoreVertIcon fontSize="small" />
                                  </IconButton>
                                </Stack>
                              </Box>
                            ))}
                          </Stack>
                        </Paper>
                      ))}
                    </Stack>
                  ) : (
                    <Typography variant="body2" color="text.secondary">
                      No plan entries match your filters.
                    </Typography>
                  )}
                </Paper>
              </>
            ) : isReviews ? (
              <>
                {Boolean(flash?.success) && (
                  <Alert severity="success">{flash.success}</Alert>
                )}
                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                  <Stack spacing={0.5} sx={{ mb: 1.5 }}>
                    <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                      Course Review Management
                    </Typography>
                    <Typography variant="body2" color="text.secondary">
                      Manage learner feedback, moderate inappropriate reviews, and keep rating quality high.
                    </Typography>
                  </Stack>
                  <Grid container spacing={1.25}>
                    <Grid item xs={12} md={4}>
                      <Paper variant="outlined" sx={{ p: 1.5, borderRadius: 1.5, height: '100%', background: 'linear-gradient(140deg, rgba(25,118,210,0.08) 0%, rgba(25,118,210,0.01) 100%)' }}>
                        <Typography variant="caption" color="text.secondary">Average Rating</Typography>
                        <Stack direction="row" spacing={1} alignItems="baseline">
                          <Typography variant="h4" sx={{ fontWeight: 800, lineHeight: 1.05 }}>
                            {Number(reviewStats?.average || 0).toFixed(1)}
                          </Typography>
                          <Typography variant="caption" color="text.secondary">out of 5</Typography>
                        </Stack>
                        <Stack direction="row" spacing={0.35} sx={{ mt: 0.5, mb: 0.25 }}>
                          {[1, 2, 3, 4, 5].map((star) => (
                            <Typography key={`avg-star-${star}`} variant="caption" color={star <= Math.round(Number(reviewStats?.average || 0)) ? 'warning.main' : 'text.disabled'}>
                              ★
                            </Typography>
                          ))}
                        </Stack>
                        <Typography variant="caption" color="text.secondary">
                          Based on {Number(reviewStats?.total || 0)} learner reviews
                        </Typography>
                      </Paper>
                    </Grid>
                    <Grid item xs={12} md={8}>
                      <Paper variant="outlined" sx={{ p: 1.5, borderRadius: 1.5 }}>
                        <Stack spacing={0.75}>
                          {normalizedReviewBreakdown.map((item) => (
                            <Box key={`review-breakdown-${item.star}`} sx={{ display: 'grid', gridTemplateColumns: '52px minmax(120px,1fr) 78px', gap: 1, alignItems: 'center' }}>
                              <Stack direction="row" spacing={0.2} alignItems="center">
                                <Typography variant="caption" color="text.secondary">{item.star}</Typography>
                                <Typography variant="caption" color="warning.main">★</Typography>
                              </Stack>
                              <Box sx={{ height: 9, width: '100%', borderRadius: 999, border: '1px solid', borderColor: 'divider', bgcolor: 'action.hover', overflow: 'hidden' }}>
                                <Box
                                  sx={{
                                    height: '100%',
                                    width: `${Math.max(0, Math.min(100, Number(item.percentage || 0)))}%`,
                                    bgcolor: item.star >= 4 ? 'success.main' : item.star === 3 ? 'warning.main' : 'error.main',
                                    transition: 'width 280ms ease',
                                  }}
                                />
                              </Box>
                              <Typography variant="caption" color="text.secondary" sx={{ textAlign: 'right', fontWeight: 600 }}>
                                {item.percentage}% · {item.count}
                              </Typography>
                            </Box>
                          ))}
                        </Stack>
                      </Paper>
                    </Grid>
                  </Grid>
                </Paper>

                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                  <Stack direction={{ xs: 'column', md: 'row' }} justifyContent="space-between" alignItems={{ xs: 'stretch', md: 'center' }} spacing={1.25} sx={{ mb: 1.5 }}>
                    <Stack direction="row" spacing={1} alignItems="center">
                      <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                        Reviews
                      </Typography>
                      <Chip size="small" label={`${filteredReviews.length} shown`} />
                    </Stack>
                    <Stack direction={{ xs: 'column', sm: 'row' }} spacing={1}>
                      <TextField
                        size="small"
                        select
                        label="Star"
                        value={reviewStarFilter}
                        onChange={(event) => setReviewStarFilter(event.target.value)}
                        sx={{ minWidth: 110 }}
                      >
                        <MenuItem value="all">All Stars</MenuItem>
                        {[5, 4, 3, 2, 1].map((star) => (
                          <MenuItem key={`review-star-filter-${star}`} value={String(star)}>
                            {star} Stars
                          </MenuItem>
                        ))}
                      </TextField>
                      <TextField
                        size="small"
                        label="Search"
                        value={reviewSearchKeyword}
                        onChange={(event) => setReviewSearchKeyword(event.target.value)}
                        sx={{ minWidth: 220 }}
                      />
                      <Button
                        size="small"
                        variant="outlined"
                        onClick={() => {
                          setReviewSearchKeyword('');
                          setReviewStarFilter('all');
                        }}
                      >
                        Clear
                      </Button>
                    </Stack>
                  </Stack>
                  {filteredReviews.length ? (
                    <Stack spacing={0.6}>
                      {filteredReviews.map((review) => (
                        <Paper key={review.id} variant="outlined" sx={{ p: 0.9, borderRadius: 1.25 }}>
                          <Stack direction={{ xs: 'column', md: 'row' }} spacing={0.75} justifyContent="space-between">
                            <Stack spacing={0.45} sx={{ minWidth: 0 }}>
                              <Stack direction="row" spacing={0.75} alignItems="center">
                                <Avatar src={review.learner_image || ''} sx={{ width: 24, height: 24 }} />
                                <Box sx={{ minWidth: 0 }}>
                                  <Typography variant="caption" sx={{ fontWeight: 700, display: 'block' }} noWrap>
                                    {review.learner_name || review.user_id}
                                  </Typography>
                                  <Typography variant="caption" color="text.secondary">
                                    {formatReviewTime(review.time)}
                                  </Typography>
                                </Box>
                              </Stack>
                              <Stack direction="row" spacing={0.5} alignItems="center">
                                <Chip size="small" label={`${Number(review.star || 0)} ★`} color={Number(review.star || 0) >= 4 ? 'success' : Number(review.star || 0) === 3 ? 'warning' : 'error'} />
                                <Typography variant="caption" color="warning.main">
                                  {'★'.repeat(Math.max(0, Number(review.star || 0)))}
                                </Typography>
                              </Stack>
                              <Typography
                                variant="caption"
                                color="text.secondary"
                                sx={{
                                  display: '-webkit-box',
                                  WebkitLineClamp: 2,
                                  WebkitBoxOrient: 'vertical',
                                  overflow: 'hidden',
                                  wordBreak: 'break-word',
                                  lineHeight: 1.25,
                                }}
                              >
                                {review.review || '-'}
                              </Typography>
                            </Stack>
                            <Stack direction="row" spacing={0.5} justifyContent="flex-end" alignItems="flex-start">
                              <IconButton size="small" color="error" onClick={() => removeReview(review)}>
                                <DeleteIcon fontSize="small" />
                              </IconButton>
                            </Stack>
                          </Stack>
                        </Paper>
                      ))}
                    </Stack>
                  ) : (
                    <Typography variant="body2" color="text.secondary">
                      No reviews match your filters.
                    </Typography>
                  )}
                </Paper>
              </>
            ) : isStudents ? (
              <>
                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                  <Stack spacing={0.5} sx={{ mb: 1.25 }}>
                    <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                      Enrolled Students
                    </Typography>
                    <Typography variant="body2" color="text.secondary">
                      Enrollment list is loaded from vipusers table for this course.
                    </Typography>
                  </Stack>
                  <Grid container spacing={1.25}>
                    <Grid item xs={12} sm={4}>
                      <Paper variant="outlined" sx={{ p: 1.25, borderRadius: 1.5 }}>
                        <Typography variant="caption" color="text.secondary">Total</Typography>
                        <Typography variant="h6" sx={{ fontWeight: 700 }}>{Number(enrollmentStats?.total || 0)}</Typography>
                      </Paper>
                    </Grid>
                    <Grid item xs={12} sm={4}>
                      <Paper variant="outlined" sx={{ p: 1.25, borderRadius: 1.5 }}>
                        <Typography variant="caption" color="text.secondary">Active</Typography>
                        <Typography variant="h6" sx={{ fontWeight: 700 }}>{Number(enrollmentStats?.active || 0)}</Typography>
                      </Paper>
                    </Grid>
                    <Grid item xs={12} sm={4}>
                      <Paper variant="outlined" sx={{ p: 1.25, borderRadius: 1.5 }}>
                        <Typography variant="caption" color="text.secondary">Deleted Accounts</Typography>
                        <Typography variant="h6" sx={{ fontWeight: 700 }}>{Number(enrollmentStats?.deleted || 0)}</Typography>
                      </Paper>
                    </Grid>
                  </Grid>
                </Paper>
                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                  <Stack direction={{ xs: 'column', md: 'row' }} justifyContent="space-between" alignItems={{ xs: 'stretch', md: 'center' }} spacing={1.25} sx={{ mb: 1.5 }}>
                    <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                      Student List ({studentsTotal})
                    </Typography>
                    <Stack direction="row" spacing={1} alignItems="center">
                      <TextField
                        size="small"
                        label="Search"
                        value={studentSearchKeyword}
                        onChange={(event) => setStudentSearchKeyword(event.target.value)}
                        onKeyDown={(event) => {
                          if (event.key === 'Enter') {
                            event.preventDefault();
                            submitStudentSearch();
                          }
                        }}
                        sx={{ minWidth: 260 }}
                      />
                      <Button variant="contained" size="small" startIcon={<SearchIcon />} onClick={submitStudentSearch}>
                        Search
                      </Button>
                    </Stack>
                  </Stack>
                  {studentRows.length ? (
                    <>
                      <TableContainer>
                        <Table size="small">
                          <TableHead>
                            <TableRow>
                              <TableCell sx={{ fontWeight: 700, width: 240, py: 0.75 }}>Learner</TableCell>
                              <TableCell sx={{ fontWeight: 700, width: 120, py: 0.75 }}>Phone</TableCell>
                              <TableCell sx={{ fontWeight: 700, width: 180, py: 0.75 }}>Email</TableCell>
                              <TableCell sx={{ fontWeight: 700, width: 120, py: 0.75 }}>Enrolled Date</TableCell>
                              <TableCell sx={{ fontWeight: 700, width: 100, py: 0.75 }}>Status</TableCell>
                              <TableCell align="right" sx={{ fontWeight: 700, width: 70, py: 0.75 }}>Actions</TableCell>
                            </TableRow>
                          </TableHead>
                          <TableBody>
                            {studentRows.map((student) => (
                              <TableRow key={`enrolled-student-${student.id}`}>
                                <TableCell sx={{ py: 0.65 }}>
                                  <Stack direction="row" spacing={1} alignItems="center">
                                    <Avatar src={student.learner_image || ''} sx={{ width: 24, height: 24 }} />
                                    <Typography variant="body2" noWrap sx={{ maxWidth: 150 }}>
                                      {student.learner_name || student.phone || 'Unknown'}
                                    </Typography>
                                  </Stack>
                                </TableCell>
                                <TableCell sx={{ py: 0.65 }}>
                                  <Typography variant="caption" noWrap>{student.phone || '-'}</Typography>
                                </TableCell>
                                <TableCell sx={{ py: 0.65 }}>
                                  <Typography variant="caption" noWrap sx={{ display: 'block', maxWidth: 170 }}>
                                    {student.learner_email || '-'}
                                  </Typography>
                                </TableCell>
                                <TableCell sx={{ py: 0.65 }}>
                                  <Typography variant="caption" noWrap>{student.date || '-'}</Typography>
                                </TableCell>
                                <TableCell sx={{ py: 0.65 }}>
                                  <Chip
                                    size="small"
                                    label={Number(student.deleted_account || 0) === 1 ? 'Deleted' : 'Active'}
                                    color={Number(student.deleted_account || 0) === 1 ? 'default' : 'success'}
                                  />
                                </TableCell>
                                <TableCell align="right" sx={{ py: 0.65, maxWidth: 120 }}>
                                  <Stack direction="row" spacing={0.75} justifyContent="flex-end">
                                    <Tooltip title="User Workspace">
                                      <span>
                                        <IconButton
                                          size="small"
                                          color="primary"
                                          component={Link}
                                          href={`${admin_app_url}/users/${encodeURIComponent(String(student.user_id || ''))}/edit`}
                                          target="_blank"
                                          rel="noreferrer"
                                          disabled={!student.user_id}
                                          sx={{ border: '1px solid', borderColor: 'divider' }}
                                        >
                                          <OpenInNewIcon fontSize="small" />
                                        </IconButton>
                                      </span>
                                    </Tooltip>
                                    <Tooltip title="Generate Certificate">
                                      <span>
                                        <IconButton
                                          size="small"
                                          color="primary"
                                          component={Link}
                                          href={`${admin_app_url}/certificate?courseId=${course.course_id}&userId=${encodeURIComponent(student.user_id || student.phone || '')}`}
                                          target="_blank"
                                          rel="noreferrer"
                                          disabled={!(student.user_id || student.phone)}
                                          sx={{ border: '1px solid', borderColor: 'divider' }}
                                        >
                                          <CertificateIcon fontSize="small" />
                                        </IconButton>
                                      </span>
                                    </Tooltip>
                                  </Stack>
                                </TableCell>
                              </TableRow>
                            ))}
                          </TableBody>
                        </Table>
                      </TableContainer>
                      <TablePagination
                        component="div"
                        count={studentsTotal}
                        page={Math.max(0, studentsPage - 1)}
                        onPageChange={(_, nextPage) => {
                          router.get(
                            `${admin_app_url}/courses/${course.course_id}/edit`,
                            {
                              studentsPage: nextPage + 1,
                              studentsPerPage,
                              studentsQ: String(enrolledStudentsFilters?.q || '').trim(),
                            },
                            {
                              preserveState: true,
                              preserveScroll: true,
                              replace: true,
                              only: ['enrolledStudents', 'enrolledStudentsFilters', 'enrollmentStats'],
                            }
                          );
                        }}
                        rowsPerPage={studentsPerPage}
                        onRowsPerPageChange={(event) => {
                          const nextPerPage = Number(event.target.value || 25);
                          router.get(
                            `${admin_app_url}/courses/${course.course_id}/edit`,
                            {
                              studentsPage: 1,
                              studentsPerPage: nextPerPage,
                              studentsQ: String(studentSearchKeyword || '').trim(),
                            },
                            {
                              preserveState: true,
                              preserveScroll: true,
                              replace: true,
                              only: ['enrolledStudents', 'enrolledStudentsFilters', 'enrollmentStats'],
                            }
                          );
                        }}
                        rowsPerPageOptions={[10, 25, 50, 100, 200]}
                      />
                    </>
                  ) : (
                    <Typography variant="body2" color="text.secondary">
                      No enrolled students found.
                    </Typography>
                  )}
                </Paper>
              </>
            ) : isEdit ? (
              <>
                {Boolean(flash?.success) && (
                  <Alert severity="success">{flash.success}</Alert>
                )}
                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                  <Stack direction={{ xs: 'column', md: 'row' }} justifyContent="space-between" spacing={1} sx={{ mb: 1.5 }}>
                    <Box>
                      <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                        Edit Course
                      </Typography>
                      <Typography variant="body2" color="text.secondary">
                        Professional editing workspace for core metadata, pricing, media, and certificate settings.
                      </Typography>
                    </Box>
                    <Stack direction="row" spacing={0.75}>
                      <Button
                        size="small"
                        variant="outlined"
                        onClick={() => {
                          resetCourseForm();
                          setCourseFormData({
                            teacher_id: Number(course?.teacher_id || 0),
                            title: String(course?.title || ''),
                            certificate_title: String(course?.certificate_title || ''),
                            lessons_count: Number(course?.lessons_count || 0),
                            cover_url: String(course?.cover_url || ''),
                            web_cover: String(course?.web_cover || ''),
                            cover_image: null,
                            web_cover_image: null,
                            description: String(course?.description || ''),
                            details: String(course?.details || ''),
                            is_vip: Number(course?.is_vip || 0) === 1,
                            active: Number(course?.active || 0) === 1,
                            duration: Number(course?.duration || 0),
                            background_color: String(course?.background_color || '#FFFFFF'),
                            fee: Number(course?.fee || 0),
                            enroll: Number(course?.enroll || 0),
                            rating: Number(course?.rating || 0),
                            major: String(course?.major || ''),
                            sorting: Number(course?.sorting || 0),
                            preview: String(course?.preview || ''),
                            preview_video: null,
                            certificate_code: String(course?.certificate_code || ''),
                          });
                          setCourseCoverFileName('');
                          setCourseWebCoverFileName('');
                          setCoursePreviewVideoFileName('');
                          setCoursePreviewVideoPreview('');
                        }}
                        disabled={courseProcessing}
                      >
                        Reset
                      </Button>
                      <Button size="small" variant="contained" onClick={submitCourseForm} disabled={courseProcessing}>
                        Save Course
                      </Button>
                    </Stack>
                  </Stack>

                  <Box component="form" onSubmit={submitCourseForm}>
                    <Stack spacing={1.5}>
                      <Paper variant="outlined" sx={{ p: 1.5, borderRadius: 1.5 }}>
                        <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1 }}>Core Information</Typography>
                        <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: 'repeat(2, minmax(0,1fr))' }, gap: 1.25 }}>
                          <TextField select label="Teacher" value={String(courseFormData.teacher_id || '')} onChange={(event) => setCourseFormData('teacher_id', Number(event.target.value || 0))} error={Boolean(courseErrors.teacher_id)} helperText={courseErrors.teacher_id}>
                            {teachers.map((teacher) => (
                              <MenuItem key={`course-teacher-${teacher.id}`} value={String(teacher.id)}>
                                {teacher.name}
                              </MenuItem>
                            ))}
                          </TextField>
                          <TextField label="Course Title" value={courseFormData.title} onChange={(event) => setCourseFormData('title', event.target.value)} error={Boolean(courseErrors.title)} helperText={courseErrors.title} />
                          <TextField
                            select
                            label="Major"
                            value={String(courseFormData.major || '')}
                            onChange={(event) => setCourseFormData('major', event.target.value)}
                            error={Boolean(courseErrors.major)}
                            helperText={courseErrors.major || (majorOptions.length ? 'Select allowed major scope' : 'No major scope access')}
                            disabled={majorOptions.length === 0}
                          >
                            {majorOptions.map((option) => (
                              <MenuItem key={`course-major-${option.value}`} value={String(option.value)}>
                                {option.label}
                              </MenuItem>
                            ))}
                          </TextField>
                          <TextField type="number" label="Duration (Days)" value={courseFormData.duration} onChange={(event) => setCourseFormData('duration', Number(event.target.value || 0))} error={Boolean(courseErrors.duration)} helperText={courseErrors.duration} />
                          <TextField select label="VIP Course" value={courseFormData.is_vip ? '1' : '0'} onChange={(event) => setCourseFormData('is_vip', event.target.value === '1')} error={Boolean(courseErrors.is_vip)} helperText={courseErrors.is_vip}>
                            <MenuItem value="1">Yes</MenuItem>
                            <MenuItem value="0">No</MenuItem>
                          </TextField>
                          <TextField select label="Active" value={courseFormData.active ? '1' : '0'} onChange={(event) => setCourseFormData('active', event.target.value === '1')} error={Boolean(courseErrors.active)} helperText={courseErrors.active}>
                            <MenuItem value="1">Active</MenuItem>
                            <MenuItem value="0">Inactive</MenuItem>
                          </TextField>
                        </Box>
                      </Paper>
                      <Paper variant="outlined" sx={{ p: 1.5, borderRadius: 1.5 }}>
                        <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1 }}>Certificate Settings</Typography>
                        <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: 'repeat(2, minmax(0,1fr))' }, gap: 1.25 }}>
                          <TextField label="Certificate Title" value={courseFormData.certificate_title} onChange={(event) => setCourseFormData('certificate_title', event.target.value)} error={Boolean(courseErrors.certificate_title)} helperText={courseErrors.certificate_title} />
                          <TextField label="Certificate Code" value={courseFormData.certificate_code} onChange={(event) => setCourseFormData('certificate_code', event.target.value)} error={Boolean(courseErrors.certificate_code)} helperText={courseErrors.certificate_code} />
                        </Box>
                      </Paper>
                      <Paper variant="outlined" sx={{ p: 1.5, borderRadius: 1.5 }}>
                        <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1 }}>Metrics & Pricing</Typography>
                        <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: 'repeat(3, minmax(0,1fr))' }, gap: 1.25 }}>
                          <TextField type="number" label="Lessons Count" value={courseFormData.lessons_count} onChange={(event) => setCourseFormData('lessons_count', Number(event.target.value || 0))} error={Boolean(courseErrors.lessons_count)} helperText={courseErrors.lessons_count} />
                          <TextField type="number" label="Fee" value={courseFormData.fee} onChange={(event) => setCourseFormData('fee', Number(event.target.value || 0))} error={Boolean(courseErrors.fee)} helperText={courseErrors.fee} />
                          <TextField type="number" label="Enroll Count" value={courseFormData.enroll} onChange={(event) => setCourseFormData('enroll', Number(event.target.value || 0))} error={Boolean(courseErrors.enroll)} helperText={courseErrors.enroll} />
                          <TextField type="number" inputProps={{ step: 0.1, min: 0, max: 5 }} label="Rating" value={courseFormData.rating} onChange={(event) => setCourseFormData('rating', Number(event.target.value || 0))} error={Boolean(courseErrors.rating)} helperText={courseErrors.rating} />
                          <TextField type="number" label="Sorting" value={courseFormData.sorting} onChange={(event) => setCourseFormData('sorting', Number(event.target.value || 0))} error={Boolean(courseErrors.sorting)} helperText={courseErrors.sorting} />
                          <Stack spacing={0.5}>
                            <TextField
                              label="Background Color"
                              value={courseFormData.background_color}
                              onChange={(event) => setCourseFormData('background_color', event.target.value)}
                              error={Boolean(courseErrors.background_color)}
                              helperText={courseErrors.background_color || 'Supports hex format like #1E88E5'}
                            />
                            <Stack direction="row" spacing={1} alignItems="center">
                              <Box
                                component="input"
                                type="color"
                                value={String(courseFormData.background_color || '#FFFFFF')}
                                onChange={(event) => setCourseFormData('background_color', event.target.value.toUpperCase())}
                                sx={{
                                  width: 42,
                                  height: 32,
                                  p: 0,
                                  border: '1px solid',
                                  borderColor: 'divider',
                                  borderRadius: 1,
                                  bgcolor: 'transparent',
                                  cursor: 'pointer',
                                }}
                              />
                              <Box sx={{ width: 18, height: 18, borderRadius: '50%', border: '1px solid', borderColor: 'divider', bgcolor: courseFormData.background_color || '#FFFFFF' }} />
                              <Typography variant="caption" color="text.secondary">
                                Pick color
                              </Typography>
                            </Stack>
                          </Stack>
                        </Box>
                      </Paper>
                      <Paper variant="outlined" sx={{ p: 1.5, borderRadius: 1.5 }}>
                        <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1 }}>Media & Links</Typography>
                        <Stack spacing={1.25}>
                          <TextField
                            label="Preview Vimeo URL"
                            value={courseFormData.preview}
                            onChange={(event) => setCourseFormData('preview', event.target.value)}
                            error={Boolean(courseErrors.preview)}
                            helperText={courseErrors.preview || 'Use Vimeo link format like lessons.link, or upload video file'}
                          />
                          <Stack direction="row" spacing={1} alignItems="center" justifyContent="space-between">
                            <Typography variant="caption" color="text.secondary" noWrap sx={{ maxWidth: 220 }}>
                              {coursePreviewVideoFileName || 'No uploaded preview video'}
                            </Typography>
                            <Button component="label" size="small" variant="outlined">
                              Upload Preview Video
                              <input hidden type="file" accept="video/mp4,video/quicktime,video/x-msvideo,video/x-matroska" onChange={handleCoursePreviewVideoChange} />
                            </Button>
                          </Stack>
                          {Boolean(videoPreviewEmbedUrl) && (
                            <Box sx={{ position: 'relative', width: '100%', pt: '56.25%', borderRadius: 1.25, overflow: 'hidden', bgcolor: 'black' }}>
                              <Box component="iframe" src={videoPreviewEmbedUrl} allow="autoplay; fullscreen; picture-in-picture" allowFullScreen sx={{ position: 'absolute', inset: 0, width: '100%', height: '100%', border: 0 }} />
                            </Box>
                          )}
                          {Boolean(directVideoPreviewUrl) && (
                            <Box sx={{ borderRadius: 1.25, overflow: 'hidden', bgcolor: 'black', aspectRatio: '16 / 9' }}>
                              <Box component="video" src={directVideoPreviewUrl} controls sx={{ width: '100%', height: '100%' }} />
                            </Box>
                          )}
                          <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: 'repeat(2, minmax(0,1fr))' }, gap: 1.25 }}>
                            <Paper variant="outlined" sx={{ p: 1.25, borderRadius: 1.25 }}>
                              <Stack spacing={1}>
                                <Typography variant="caption" sx={{ fontWeight: 700 }}>Cover Image</Typography>
                                <TextField label="Cover URL" value={courseFormData.cover_url} onChange={(event) => setCourseFormData('cover_url', event.target.value)} error={Boolean(courseErrors.cover_url)} helperText={courseErrors.cover_url || 'You can use URL or upload'} />
                                <Stack direction="row" spacing={1} alignItems="center" justifyContent="space-between">
                                  <Typography variant="caption" color="text.secondary" noWrap sx={{ maxWidth: 180 }}>
                                    {courseCoverFileName || 'No uploaded image'}
                                  </Typography>
                                  <Button component="label" size="small" variant="outlined">
                                    Upload
                                    <input hidden type="file" accept="image/*" onChange={handleCourseCoverChange} />
                                  </Button>
                                </Stack>
                                <Box sx={{ borderRadius: 1, overflow: 'hidden', bgcolor: 'action.hover', height: 110 }}>
                                  {(courseFormData.cover_image || courseFormData.cover_url) ? (
                                    <Box component="img" src={courseFormData.cover_image ? URL.createObjectURL(courseFormData.cover_image) : courseFormData.cover_url} alt="Cover" sx={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                                  ) : null}
                                </Box>
                              </Stack>
                            </Paper>
                            <Paper variant="outlined" sx={{ p: 1.25, borderRadius: 1.25 }}>
                              <Stack spacing={1}>
                                <Typography variant="caption" sx={{ fontWeight: 700 }}>Web Cover (16:9)</Typography>
                                <TextField label="Web Cover URL" value={courseFormData.web_cover} onChange={(event) => setCourseFormData('web_cover', event.target.value)} error={Boolean(courseErrors.web_cover)} helperText={courseErrors.web_cover || 'URL or 16:9 cropped upload'} />
                                <Stack direction="row" spacing={1} alignItems="center" justifyContent="space-between">
                                  <Typography variant="caption" color="text.secondary" noWrap sx={{ maxWidth: 180 }}>
                                    {courseWebCoverFileName || 'No uploaded image'}
                                  </Typography>
                                  <Button component="label" size="small" variant="outlined">
                                    Upload & Crop
                                    <input hidden type="file" accept="image/*" onChange={handleCourseWebCoverChange} />
                                  </Button>
                                </Stack>
                                <Box sx={{ borderRadius: 1, overflow: 'hidden', bgcolor: 'action.hover', aspectRatio: '16 / 9' }}>
                                  {(courseFormData.web_cover_image || courseFormData.web_cover) ? (
                                    <Box component="img" src={courseFormData.web_cover_image ? URL.createObjectURL(courseFormData.web_cover_image) : courseFormData.web_cover} alt="Web Cover" sx={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                                  ) : null}
                                </Box>
                              </Stack>
                            </Paper>
                          </Box>
                        </Stack>
                      </Paper>
                      <Paper variant="outlined" sx={{ p: 1.5, borderRadius: 1.5 }}>
                        <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1 }}>Content</Typography>
                        <Stack spacing={1.25}>
                          <TextField label="Description" value={courseFormData.description} onChange={(event) => setCourseFormData('description', event.target.value)} error={Boolean(courseErrors.description)} helperText={courseErrors.description} />
                          <Paper variant="outlined" sx={{ borderRadius: 1.25, overflow: 'hidden' }}>
                            <Stack direction="row" spacing={0.25} sx={{ p: 0.5, borderBottom: '1px solid', borderColor: 'divider', bgcolor: 'action.hover' }}>
                              <Tooltip title="Bold">
                                <IconButton size="small" onClick={formatDetailsBold}>
                                  <FormatBoldIcon fontSize="small" />
                                </IconButton>
                              </Tooltip>
                              <Tooltip title="Italic">
                                <IconButton size="small" onClick={formatDetailsItalic}>
                                  <FormatItalicIcon fontSize="small" />
                                </IconButton>
                              </Tooltip>
                              <Tooltip title="Bulleted List">
                                <IconButton size="small" onClick={() => formatDetailsList(false)}>
                                  <FormatListBulletedIcon fontSize="small" />
                                </IconButton>
                              </Tooltip>
                              <Tooltip title="Numbered List">
                                <IconButton size="small" onClick={() => formatDetailsList(true)}>
                                  <FormatListNumberedIcon fontSize="small" />
                                </IconButton>
                              </Tooltip>
                            </Stack>
                            <Box
                              ref={courseDetailsRef}
                              contentEditable
                              suppressContentEditableWarning
                              onInput={(event) => {
                                courseDetailsDraftRef.current = String(event.currentTarget.innerHTML || '');
                              }}
                              onFocus={() => setCourseDetailsFocused(true)}
                              onBlur={(event) => {
                                setCourseDetailsFocused(false);
                                setCourseFormData('details', String(event.currentTarget.innerHTML || ''));
                              }}
                              sx={{
                                minHeight: 220,
                                p: 1.5,
                                outline: 'none',
                                fontSize: 14,
                                lineHeight: 1.5,
                                '& ul, & ol': { pl: 3, my: 0.75 },
                                '& p': { my: 0.75 },
                              }}
                            />
                            <Typography variant="caption" color={courseErrors.details ? 'error.main' : 'text.secondary'} sx={{ px: 1.25, pb: 1 }}>
                              {courseErrors.details || 'Stored as HTML. Use toolbar for bold, italic, and lists.'}
                            </Typography>
                          </Paper>
                        </Stack>
                      </Paper>
                      <Stack direction="row" justifyContent="flex-end">
                        <Button type="submit" variant="contained" disabled={courseProcessing}>
                          Update Course
                        </Button>
                      </Stack>
                    </Stack>
                  </Box>
                </Paper>
              </>
            ) : (
              <>
                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                  <Stack direction="row" spacing={1} alignItems="center" sx={{ mb: 1 }}>
                    <AutoAwesomeIcon color="primary" fontSize="small" />
                    <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                      {currentPlaceholder.title}
                    </Typography>
                  </Stack>
                  <Typography variant="body2" color="text.secondary">
                    {currentPlaceholder.description}
                  </Typography>
                </Paper>
                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                  <Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 1 }}>
                    Planned Blocks
                  </Typography>
                  <List dense disablePadding>
                    {currentPlaceholder.blocks.map((block) => (
                      <ListItem key={block} disablePadding sx={{ py: 0.25 }}>
                        <ListItemText
                          primary={block}
                          primaryTypographyProps={{ variant: 'body2', color: 'text.secondary' }}
                        />
                      </ListItem>
                    ))}
                  </List>
                </Paper>
                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                  <Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 1 }}>
                    Implementation Note
                  </Typography>
                  <Typography variant="body2" color="text.secondary">
                    This placeholder is separated by right-side menu state, so each menu item can be implemented independently without layout rework.
                  </Typography>
                </Paper>
              </>
            )}
          </Stack>

          <Paper variant="outlined" sx={{ borderRadius: 2, overflow: 'hidden', alignSelf: { xs: 'stretch', md: 'start' }, position: { md: 'sticky' }, top: { md: 72 } }}>
            <Box sx={{ p: 2, borderBottom: '1px solid', borderColor: 'divider' }}>
              <Box sx={{ height: 110, borderRadius: 1.5, bgcolor: 'action.hover', overflow: 'hidden', display: 'flex', alignItems: 'center', justifyContent: 'center', mb: 1.25 }}>
                {course?.web_cover ? (
                  <Box component="img" src={course.web_cover} alt={course?.title || 'Course cover'} sx={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                ) : (
                  <SchoolIcon color="action" />
                )}
              </Box>
              <Typography variant="subtitle2" sx={{ fontWeight: 700 }} noWrap>
                {course?.title || 'Course Workspace'}
              </Typography>
              <Stack direction="row" spacing={0.5} alignItems="center" sx={{ mt: 0.35, flexWrap: 'wrap' }}>
                {Boolean(course?.major) && <Chip size="small" label={String(course.major)} />}
                <Chip size="small" label={Number(course?.is_vip || 0) === 1 ? 'VIP' : 'Free'} color={Number(course?.is_vip || 0) === 1 ? 'warning' : 'default'} />
                <Chip size="small" label={Number(course?.active || 0) === 1 ? 'Active' : 'Inactive'} color={Number(course?.active || 0) === 1 ? 'success' : 'default'} />
              </Stack>
              <Typography variant="caption" color="text.secondary" sx={{ mt: 0.65, display: 'block' }} noWrap>
                Teacher: {courseTeacherName || 'Unassigned'}
              </Typography>
              <Typography variant="caption" color="text.secondary" sx={{ display: 'block' }}>
                Enroll {Number(course?.enroll || 0)} · Lessons {Number(course?.lessons_count || 0)}
              </Typography>
            </Box>
            <List dense sx={{ p: 1 }}>
              {navItems.map((item) => (
                <ListItem key={item.key} disablePadding sx={{ mb: 0.25 }}>
                  <ListItemButton selected={activeMenuKey === item.key} sx={{ borderRadius: 1 }} onClick={() => setActiveMenuKey(item.key)}>
                    <ListItemIcon sx={{ minWidth: 34 }}>{item.icon}</ListItemIcon>
                    <ListItemText primary={item.label} primaryTypographyProps={{ fontSize: 13, fontWeight: activeMenuKey === item.key ? 700 : 500 }} />
                  </ListItemButton>
                </ListItem>
              ))}
            </List>
            {isMobile && <Divider />}
          </Paper>
        </Box>
      </Stack>

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
            if (categoryMenuTarget) {
              if (confirm(`Set all lessons in "${categoryMenuTarget.category_title}" to VIP?`)) {
                router.post(
                  `${admin_app_url}/courses/${course.course_id}/categories/${categoryMenuTarget.id}/lessons/vip-bulk`,
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
            if (categoryMenuTarget) {
              if (confirm(`Remove VIP from all lessons in "${categoryMenuTarget.category_title}"?`)) {
                router.post(
                  `${admin_app_url}/courses/${course.course_id}/categories/${categoryMenuTarget.id}/lessons/vip-bulk`,
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
        anchorEl={curriculumLessonMenuAnchorEl}
        open={Boolean(curriculumLessonMenuAnchorEl)}
        onClose={closeCurriculumLessonMenu}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
        transformOrigin={{ vertical: 'top', horizontal: 'right' }}
      >
        {Number(curriculumLessonMenuTarget?.isVideo || 0) === 1 && curriculumLessonMenuTarget && selectedCategory && (
          <MenuItem
            onClick={() => {
              const lesson = curriculumLessonMenuTarget;
              closeCurriculumLessonMenu();
              router.visit(
                `${admin_app_url}/courses/${course.course_id}/categories/${selectedCategory.id}/lessons/${lesson.id}/video-detail`
              );
            }}
          >
            <ListItemIcon sx={{ minWidth: 30 }}>
              <AutoAwesomeIcon fontSize="small" />
            </ListItemIcon>
            <ListItemText primary="Video Detail" />
          </MenuItem>
        )}
        {Number(curriculumLessonMenuTarget?.isVideo || 0) !== 1 && Boolean(curriculumLessonMenuTarget?.document_link) && curriculumLessonMenuTarget && selectedCategory && (
          <MenuItem
            onClick={() => {
              const lesson = curriculumLessonMenuTarget;
              closeCurriculumLessonMenu();
              router.visit(
                `${admin_app_url}/courses/${course.course_id}/categories/${selectedCategory.id}/lessons/${lesson.id}/html`
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
            const lesson = curriculumLessonMenuTarget;
            closeCurriculumLessonMenu();
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
            const lesson = curriculumLessonMenuTarget;
            closeCurriculumLessonMenu();
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

      <Menu
        anchorEl={dailyPlanMenuAnchorEl}
        open={Boolean(dailyPlanMenuAnchorEl)}
        onClose={closeDailyPlanMenu}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
        transformOrigin={{ vertical: 'top', horizontal: 'right' }}
      >
        <MenuItem
          onClick={() => {
            const entry = dailyPlanMenuTarget;
            closeDailyPlanMenu();
            if (entry) {
              openEditStudyPlan(entry);
            }
          }}
        >
          <ListItemIcon sx={{ minWidth: 30 }}>
            <EditIcon fontSize="small" />
          </ListItemIcon>
          <ListItemText primary="Edit" />
        </MenuItem>
        <MenuItem
          onClick={() => {
            const entry = dailyPlanMenuTarget;
            closeDailyPlanMenu();
            if (entry) {
              removeStudyPlan(entry);
            }
          }}
        >
          <ListItemIcon sx={{ minWidth: 30 }}>
            <DeleteIcon fontSize="small" color="error" />
          </ListItemIcon>
          <ListItemText primary="Delete" />
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
                onRemoveLectureNote={removeLectureNote}
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
                <TextField
                  label="Major Keyword"
                  value={course?.major || ''}
                  InputProps={{ readOnly: true }}
                  helperText="Auto-filled from current course"
                />
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
                onRemoveLectureNote={removeLectureNote}
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
            <Button type="submit" variant="contained" disabled={lessonProcessing}>Update Lesson</Button>
          </DialogActions>
        </Box>
      </Dialog>

      <Dialog open={reviewDialogOpen} onClose={closeReviewDialog} maxWidth="sm" fullWidth>
        <DialogTitle>Edit Review</DialogTitle>
        <Box component="form" onSubmit={submitReviewForm}>
          <DialogContent dividers>
            <Stack spacing={1.5}>
              <TextField
                select
                label="Star"
                value={String(reviewFormData.star)}
                onChange={(event) => setReviewFormData('star', Number(event.target.value))}
                error={Boolean(reviewErrors.star)}
                helperText={reviewErrors.star}
              >
                {[5, 4, 3, 2, 1].map((star) => (
                  <MenuItem key={`edit-review-star-${star}`} value={String(star)}>
                    {star} Stars
                  </MenuItem>
                ))}
              </TextField>
              <TextField
                multiline
                minRows={4}
                label="Review"
                value={reviewFormData.review}
                onChange={(event) => setReviewFormData('review', event.target.value)}
                error={Boolean(reviewErrors.review)}
                helperText={reviewErrors.review}
              />
            </Stack>
          </DialogContent>
          <DialogActions>
            <Button onClick={closeReviewDialog} disabled={reviewProcessing}>Cancel</Button>
            <Button type="submit" variant="contained" disabled={reviewProcessing}>Update Review</Button>
          </DialogActions>
        </Box>
      </Dialog>

      <ImageCropper
        open={thumbCropperOpen}
        image={thumbTempImage}
        onCropComplete={handleCreateLessonThumbCropComplete}
        onCancel={() => {
          setThumbCropperOpen(false);
          setThumbTempImage(null);
        }}
        aspect={16 / 9}
        title="Crop Lesson Thumbnail"
      />
      <ImageCropper
        open={webCoverCropperOpen}
        image={webCoverTempImage}
        onCropComplete={handleCourseWebCoverCropComplete}
        onCancel={() => {
          setWebCoverCropperOpen(false);
          setWebCoverTempImage(null);
        }}
        aspect={16 / 9}
        title="Crop Web Cover (16:9)"
      />
      </Box>
    </ThemeProvider>
  );
}

CourseEdit.layout = (page) => <AdminLayout children={page} />;
