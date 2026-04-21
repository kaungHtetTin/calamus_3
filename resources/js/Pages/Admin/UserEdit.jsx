import React, { useEffect, useMemo, useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import {
  Alert,
  Avatar,
  Box,
  Button,
  Chip,
  Divider,
  Grid,
  List,
  ListItem,
  ListItemButton,
  ListItemIcon,
  ListItemText,
  MenuItem,
  Paper,
  Snackbar,
  Stack,
  Collapse,
  Tooltip,
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
  FormControlLabel,
  Checkbox,
} from '@mui/material';
import { ThemeProvider, createTheme } from '@mui/material/styles';
import {
  WorkspacePremium as CertificateIcon,
  Person as PersonIcon,
  Star as StarIcon,
  Notifications as NotificationsIcon,
  Email as EmailIcon,
  Security as SecurityIcon,
  Delete as DeleteIcon,
  ExpandMore as ExpandMoreIcon,
} from '@mui/icons-material';
import IconButton from '@mui/material/IconButton';

export default function UserEdit({ user, userData = [], vipCourses = [], certificates = [], profileStats = {}, canManageVipAccess = false, canDeleteUser = false }) {
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
  const [activeMenuKey, setActiveMenuKey] = useState('profile');
  const [openSnackbar, setOpenSnackbar] = useState(false);
  const [vipSavingLang, setVipSavingLang] = useState(null);
  const [vipErrorsByLang, setVipErrorsByLang] = useState({});
  const [pushForm, setPushForm] = useState({
    title: '',
    body: '',
    major: 'all',
    platform: 'all',
  });
  const [pushSending, setPushSending] = useState(false);
  const [pushErrors, setPushErrors] = useState({});
  const [emailForm, setEmailForm] = useState({ title: '', body: '' });
  const [emailSending, setEmailSending] = useState(false);
  const [emailErrors, setEmailErrors] = useState({});
  const navItems = useMemo(() => {
    const items = [
      { key: 'profile', label: 'Profile', icon: <PersonIcon fontSize="small" /> },
      { key: 'push', label: 'Push Notification', icon: <NotificationsIcon fontSize="small" /> },
      { key: 'email', label: 'Email', icon: <EmailIcon fontSize="small" /> },
      { key: 'security', label: 'Account Security', icon: <SecurityIcon fontSize="small" /> },
    ];

    if (canManageVipAccess) {
      items.splice(1, 0, { key: 'vip-access', label: 'VIP Access', icon: <StarIcon fontSize="small" /> });
    }

    return items;
  }, [canManageVipAccess]);

  const activeMenu = useMemo(
    () => navItems.find((item) => item.key === activeMenuKey) || navItems[0],
    [activeMenuKey, navItems]
  );
  const isProfile = activeMenu.key === 'profile';
  const isVipAccess = activeMenu.key === 'vip-access';
  const isPush = activeMenu.key === 'push';
  const isEmail = activeMenu.key === 'email';
  const isSecurity = activeMenu.key === 'security';

  useEffect(() => {
    if (!canManageVipAccess && activeMenuKey === 'vip-access') {
      setActiveMenuKey('profile');
    }
  }, [canManageVipAccess, activeMenuKey]);

  const securityForm = useForm({
    learner_name: user?.learner_name || '',
    learner_email: user?.learner_email || '',
    learner_phone: user?.learner_phone || '',
    gender: user?.gender || '',
    password: '',
  });

  const userDataRows = useMemo(() => {
    return (Array.isArray(userData) ? userData : []).map((row) => {
      let token = null;
      if (row?.token && typeof row.token === 'object') {
        token = row.token;
      } else if (typeof row?.token === 'string') {
        try {
          const parsed = JSON.parse(row.token);
          if (parsed && typeof parsed === 'object') {
            token = parsed;
          }
        } catch (e) {
          token = null;
        }
      }
      const hasAndroidToken = Boolean(token?.android);
      const hasIosToken = Boolean(token?.ios);
      return {
        id: row?.id,
        major: row?.major ? String(row.major) : '',
        languageLabel: String(row?.language_display_name || row?.display_name || row?.language_name || row?.major || '').trim(),
        languageImagePath: String(row?.language_image_path || '').trim(),
        isVip: Number(row?.is_vip || 0) === 1,
        diamondPlan: Number(row?.diamond_plan || 0) === 1,
        lastActive: row?.last_active || null,
        firstJoin: row?.first_join || null,
        hasAndroidToken,
        hasIosToken,
      };
    });
  }, [userData]);
  const hasAnyVip = useMemo(() => userDataRows.some((row) => row.isVip), [userDataRows]);
  const hasDiamondPlan = useMemo(() => userDataRows.some((row) => row.diamondPlan), [userDataRows]);
  const userImageUrl = useMemo(() => {
    if (!user?.learner_image) {
      return '';
    }
    const value = String(user.learner_image || '').trim();
    if (value.startsWith('http://') || value.startsWith('https://') || value.startsWith('//')) {
      return value;
    }
    return `${admin_app_url.replace('/admin', '')}/uploads/admin/images/${value}`;
  }, [admin_app_url, user?.learner_image]);
  const userCoverUrl = useMemo(() => {
    if (!user?.cover_image) {
      return '';
    }
    const value = String(user.cover_image || '').trim();
    if (value.startsWith('http://') || value.startsWith('https://') || value.startsWith('//')) {
      return value;
    }
    return `${admin_app_url.replace('/admin', '')}/uploads/admin/images/${value}`;
  }, [admin_app_url, user?.cover_image]);

  const vipCoursesRows = useMemo(() => {
    return (Array.isArray(vipCourses) ? vipCourses : []).map((row) => {
      return {
        course_id: Number(row?.course_id || 0),
        title: String(row?.title || ''),
        major: String(row?.major || ''),
        is_vip: Number(row?.is_vip || 0),
        active: Number(row?.active || 0),
        languageLabel: String(row?.language_display_name || row?.display_name || row?.language_name || row?.major || '').trim(),
        languageImagePath: String(row?.language_image_path || '').trim(),
      };
    });
  }, [vipCourses]);
  const certificateRows = useMemo(() => {
    return Array.isArray(certificates) ? certificates : [];
  }, [certificates]);

  const appBaseUrl = useMemo(() => admin_app_url.replace(/\/admin\/?$/, ''), [admin_app_url]);
  const buildImageUrl = (path) => {
    if (!path) {
      return '';
    }
    const normalized = String(path).trim();
    if (normalized.startsWith('http://') || normalized.startsWith('https://')) {
      return normalized;
    }
    const normalizedPath = normalized.startsWith('/') ? normalized : `/${normalized}`;
    return `${appBaseUrl}${normalizedPath}`;
  };

  const { courseCatalog = [] } = usePage().props;
  const courseCatalogRows = useMemo(() => {
    return (Array.isArray(courseCatalog) ? courseCatalog : []).map((row) => ({
      course_id: Number(row?.course_id || 0),
      title: String(row?.title || ''),
      major: String(row?.major || ''),
      languageLabel: String(row?.language_display_name || row?.display_name || row?.language_name || row?.major || '').trim(),
      languageImagePath: String(row?.language_image_path || '').trim(),
      is_vip: Number(row?.is_vip || 0),
      active: Number(row?.active || 0),
      sorting: Number(row?.sorting || 0),
    }));
  }, [courseCatalog]);
  const purchasedCourseIds = useMemo(() => new Set(vipCoursesRows.map((c) => c.course_id)), [vipCoursesRows]);
  const diamondMajors = useMemo(() => new Set(userDataRows.filter((r) => r.diamondPlan).map((r) => (r.major || '').toLowerCase())), [userDataRows]);
  const groupedCourses = useMemo(() => {
    const groups = {};
    courseCatalogRows.forEach((c) => {
      const key = c.languageLabel || c.major || 'Unknown';
      if (!groups[key]) groups[key] = [];
      groups[key].push(c);
    });
    Object.keys(groups).forEach((k) => {
      groups[k].sort((a, b) => b.sorting - a.sorting || a.title.localeCompare(b.title));
    });
    return groups;
  }, [courseCatalogRows]);
  const pushTokenByMajor = useMemo(() => {
    const map = {};
    userDataRows.forEach((row) => {
      const major = String(row.major || '').trim();
      if (!major) return;
      const key = major.toLowerCase();
      if (!map[key]) {
        map[key] = {
          major,
          label: row.languageLabel || major,
          hasAndroid: false,
          hasIos: false,
        };
      }
      map[key].hasAndroid = map[key].hasAndroid || Boolean(row.hasAndroidToken);
      map[key].hasIos = map[key].hasIos || Boolean(row.hasIosToken);
    });
    return map;
  }, [userDataRows]);
  const pushMajorOptions = useMemo(() => {
    const platform = pushForm.platform;
    return Object.values(pushTokenByMajor)
      .filter((opt) => {
        if (platform === 'android') return opt.hasAndroid;
        if (platform === 'ios') return opt.hasIos;
        return opt.hasAndroid || opt.hasIos;
      })
      .sort((a, b) => a.label.localeCompare(b.label));
  }, [pushForm.platform, pushTokenByMajor]);
  const pushPlatformOptions = useMemo(() => {
    const majorKey = String(pushForm.major || '').toLowerCase();
    const majorInfo = pushTokenByMajor[majorKey];
    const hasAndroid = majorInfo ? majorInfo.hasAndroid : Object.values(pushTokenByMajor).some((v) => v.hasAndroid);
    const hasIos = majorInfo ? majorInfo.hasIos : Object.values(pushTokenByMajor).some((v) => v.hasIos);
    const options = ['all'];
    if (hasAndroid) options.push('android');
    if (hasIos) options.push('ios');
    return options;
  }, [pushForm.major, pushTokenByMajor]);
  useEffect(() => {
    if (!isPush) return;
    const allowedPlatforms = pushPlatformOptions;
    if (!allowedPlatforms.includes(pushForm.platform)) {
      setPushForm((prev) => ({ ...prev, platform: allowedPlatforms[0] || 'all' }));
    }
  }, [isPush, pushForm.platform, pushPlatformOptions]);
  useEffect(() => {
    if (!isPush) return;
    const majors = pushMajorOptions.map((o) => o.major.toLowerCase());
    if (pushForm.major !== 'all' && !majors.includes(String(pushForm.major).toLowerCase())) {
      const nextMajor = pushMajorOptions[0]?.major || 'all';
      setPushForm((prev) => ({ ...prev, major: nextMajor }));
    }
  }, [isPush, pushForm.major, pushMajorOptions]);
  const [vipAccessByLanguage, setVipAccessByLanguage] = useState(() => {
    const initial = {};
    Object.entries(groupedCourses).forEach(([lang, courses]) => {
      const selected = courses.filter((c) => purchasedCourseIds.has(c.course_id)).map((c) => c.course_id);
      initial[lang] = {
        vipAccess: selected.length > 0,
        diamondPlan: diamondMajors.has((courses[0]?.major || '').toLowerCase()),
        amount: '',
        partnerCode: '',
        selectedCourseIds: selected,
      };
    });
    return initial;
  });
  const updateVipLanguage = (lang, patch) => {
    setVipAccessByLanguage((prev) => ({ ...prev, [lang]: { ...(prev[lang] || {}), ...patch } }));
  };
  const toggleCourseSelection = (lang, courseId, checked) => {
    setVipAccessByLanguage((prev) => {
      const current = prev[lang] || { selectedCourseIds: [] };
      const setIds = new Set(current.selectedCourseIds || []);
      if (checked) setIds.add(courseId);
      else setIds.delete(courseId);
      return { ...prev, [lang]: { ...current, selectedCourseIds: Array.from(setIds) } };
    });
  };
  const [expandedLangs, setExpandedLangs] = useState(() => new Set());
  const toggleLangCollapse = (lang) => {
    setExpandedLangs((prev) => {
      const next = new Set(prev);
      if (next.has(lang)) next.delete(lang);
      else next.add(lang);
      return next;
    });
  };
  const saveVipLanguage = (lang, courses) => {
    const langState = vipAccessByLanguage[lang] || {};
    const major = String(courses?.[0]?.major || '').trim();
    setVipErrorsByLang((prev) => ({ ...prev, [lang]: {} }));
    setVipSavingLang(lang);

    router.post(
      `${admin_app_url}/users/${user.user_id}/vip-access`,
      {
        major,
        selected_course_ids: langState.selectedCourseIds || [],
        vip_access: Boolean(langState.vipAccess),
        diamond_plan: Boolean(langState.diamondPlan),
        amount: langState.amount === '' ? null : langState.amount,
        partner_code: String(langState.partnerCode || '').trim() || null,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          setOpenSnackbar(true);
        },
        onError: (errors) => {
          setVipErrorsByLang((prev) => ({ ...prev, [lang]: errors }));
        },
        onFinish: () => {
          setVipSavingLang((current) => (current === lang ? null : current));
        },
      }
    );
  };

  const sendPush = () => {
    setPushErrors({});
    setPushSending(true);
    router.post(
      `${admin_app_url}/users/${user.user_id}/push`,
      {
        title: pushForm.title,
        body: pushForm.body,
        major: pushForm.major,
        platform: pushForm.platform,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          setOpenSnackbar(true);
        },
        onError: (errors) => {
          setPushErrors(errors || {});
        },
        onFinish: () => {
          setPushSending(false);
        },
      }
    );
  };

  const sendEmail = () => {
    setEmailErrors({});
    setEmailSending(true);
    router.post(
      `${admin_app_url}/users/${user.user_id}/email`,
      {
        title: emailForm.title,
        body: emailForm.body,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          setOpenSnackbar(true);
        },
        onError: (errors) => {
          setEmailErrors(errors || {});
        },
        onFinish: () => {
          setEmailSending(false);
        },
      }
    );
  };

  const normalizedProfileStats = useMemo(() => {
    return {
      firstJoin: profileStats.first_join || null,
      lastActive: profileStats.last_active || null,
      totalPosts: Number(profileStats.total_posts || 0),
      totalComments: Number(profileStats.total_comments || 0),
    };
  }, [profileStats]);

  const formatDateTime = (value) => {
    if (!value) return '-';
    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) {
      return String(value);
    }
    return parsed.toLocaleString();
  };

  const handleSavePassword = (event) => {
    event.preventDefault();
    securityForm.patch(`${admin_app_url}/users/${user.user_id}`, {
      preserveScroll: true,
      onSuccess: () => {
        securityForm.setData('password', '');
        setOpenSnackbar(true);
      },
    });
  };

  const handleDeleteUser = () => {
    if (!canDeleteUser) return;
    const confirmText = window.prompt(`Type DELETE to remove user ${user.user_id}.`);
    if (confirmText !== 'DELETE') return;
    router.delete(`${admin_app_url}/users/${user.user_id}`, {
      preserveScroll: true,
    });
  };

  return (
    <ThemeProvider theme={compactTheme}>
      <Box>
        <Head title="User Edit Workspace" />
        <Stack spacing={2}>
          <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: { xs: 'flex-start', md: 'center' }, flexDirection: { xs: 'column', md: 'row' }, gap: 1 }}>
            <Box>
              <Typography variant="h5" sx={{ fontWeight: 700 }}>
                User Edit Workspace
              </Typography>
              <Typography variant="body2" color="text.secondary">
                {user ? `User: ${user.learner_name || user.user_id}` : 'User management workspace'}
              </Typography>
            </Box>
            <Stack direction="row" spacing={1} alignItems="center">
              <Button variant="contained" component={Link} href={`${admin_app_url}/users`} startIcon={<PersonIcon />}>
                Users
              </Button>
              {canDeleteUser ? (
                <Button variant="outlined" color="error" onClick={handleDeleteUser} startIcon={<DeleteIcon />}>
                  Delete User
                </Button>
              ) : null}
            </Stack>
          </Box>

          <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: 'minmax(0,1fr) 260px' }, gap: 2 }}>
            <Stack spacing={2}>
              {isProfile && (
                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                  <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1 }}>
                    <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                      Profile
                    </Typography>
                    <Stack direction="row" spacing={0.5} useFlexGap flexWrap="wrap" justifyContent="flex-end">
                      <Chip size="small" label={`ID ${user.user_id}`} />
                      {user.email_verified_at ? <Chip size="small" color="success" label="Verified" /> : <Chip size="small" color="warning" label="Unverified" />}
                    </Stack>
                  </Stack>
                  <Divider sx={{ mb: 2 }} />
                  <Grid container spacing={1.5}>
                    <Grid item xs={12} md={6}>
                      <Typography variant="caption" color="text.secondary">Full Name</Typography>
                      <Typography variant="body2" sx={{ fontWeight: 600 }}>{user?.learner_name || '-'}</Typography>
                    </Grid>
                    <Grid item xs={12} md={6}>
                      <Typography variant="caption" color="text.secondary">Email</Typography>
                      <Typography variant="body2" sx={{ fontWeight: 600 }}>{user?.learner_email || '-'}</Typography>
                    </Grid>
                    <Grid item xs={12} md={6}>
                      <Typography variant="caption" color="text.secondary">Phone</Typography>
                      <Typography variant="body2" sx={{ fontWeight: 600 }}>{user?.learner_phone || '-'}</Typography>
                    </Grid>
                    <Grid item xs={12} md={6}>
                      <Typography variant="caption" color="text.secondary">Gender</Typography>
                      <Typography variant="body2" sx={{ fontWeight: 600 }}>{user?.gender || '-'}</Typography>
                    </Grid>
                    <Grid item xs={12} md={6}>
                      <Typography variant="caption" color="text.secondary">Region</Typography>
                      <Typography variant="body2" sx={{ fontWeight: 600 }}>{user?.region || '-'}</Typography>
                    </Grid>
                    <Grid item xs={12} md={6}>
                      <Typography variant="caption" color="text.secondary">Education</Typography>
                      <Typography variant="body2" sx={{ fontWeight: 600 }}>{user?.education || '-'}</Typography>
                    </Grid>
                    <Grid item xs={12} md={6}>
                      <Typography variant="caption" color="text.secondary">Work</Typography>
                      <Typography variant="body2" sx={{ fontWeight: 600 }}>{user?.work || '-'}</Typography>
                    </Grid>
                    <Grid item xs={12} md={6}>
                      <Typography variant="caption" color="text.secondary">Bio</Typography>
                      <Typography variant="body2" sx={{ fontWeight: 600, overflowWrap: 'anywhere' }}>{user?.bio || '-'}</Typography>
                    </Grid>
                  </Grid>
                  <Divider sx={{ my: 2 }} />
                  <Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 1 }}>
                    Activity Performance
                  </Typography>
                  <Grid container spacing={1.5}>
                    <Grid item xs={12} sm={6} md={3}>
                      <Paper variant="outlined" sx={{ p: 1.5, borderRadius: 2 }}>
                        <Typography variant="caption" color="text.secondary">First Join</Typography>
                        <Typography variant="body2" sx={{ fontWeight: 700 }}>{formatDateTime(normalizedProfileStats.firstJoin)}</Typography>
                      </Paper>
                    </Grid>
                    <Grid item xs={12} sm={6} md={3}>
                      <Paper variant="outlined" sx={{ p: 1.5, borderRadius: 2 }}>
                        <Typography variant="caption" color="text.secondary">Last Active</Typography>
                        <Typography variant="body2" sx={{ fontWeight: 700 }}>{formatDateTime(normalizedProfileStats.lastActive)}</Typography>
                      </Paper>
                    </Grid>
                    <Grid item xs={12} sm={6} md={3}>
                      <Paper variant="outlined" sx={{ p: 1.5, borderRadius: 2 }}>
                        <Typography variant="caption" color="text.secondary">Total Posts</Typography>
                        <Typography variant="h6" sx={{ fontWeight: 700 }}>{normalizedProfileStats.totalPosts}</Typography>
                      </Paper>
                    </Grid>
                    <Grid item xs={12} sm={6} md={3}>
                      <Paper variant="outlined" sx={{ p: 1.5, borderRadius: 2 }}>
                        <Typography variant="caption" color="text.secondary">Total Comments</Typography>
                        <Typography variant="h6" sx={{ fontWeight: 700 }}>{normalizedProfileStats.totalComments}</Typography>
                      </Paper>
                    </Grid>
                    <Grid item xs={12}>
                      <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                        <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1 }}>
                          <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                            Certificate List
                          </Typography>
                          <Chip size="small" label={`${certificateRows.length} items`} />
                        </Stack>
                        <Divider sx={{ mb: 1.5 }} />
                        <TableContainer component={Box}>
                          <Table>
                            <TableHead>
                              <TableRow>
                                <TableCell>Ref</TableCell>
                                <TableCell>Course</TableCell>
                                <TableCell>Major</TableCell>
                                <TableCell>Date</TableCell>
                                <TableCell align="right">Action</TableCell>
                              </TableRow>
                            </TableHead>
                            <TableBody>
                              {certificateRows.map((row) => (
                                <TableRow key={`cert-${row.id}`}>
                                  <TableCell>
                                    <Typography variant="body2" sx={{ fontWeight: 700 }}>{row.ref || '-'}</Typography>
                                    <Typography variant="caption" color="text.secondary">ID: {row.id}</Typography>
                                  </TableCell>
                                  <TableCell>
                                    <Typography variant="body2" sx={{ fontWeight: 600 }}>{row.title || `Course ${row.course_id}`}</Typography>
                                    <Typography variant="caption" color="text.secondary">Course ID: {row.course_id}</Typography>
                                  </TableCell>
                                  <TableCell>{row.major || '-'}</TableCell>
                                  <TableCell>{row.date ? String(row.date) : '-'}</TableCell>
                                  <TableCell align="right">
                                    <Tooltip title="Generate Certificate">
                                      <span>
                                        <IconButton
                                          size="small"
                                          color="primary"
                                          component={Link}
                                          href={`${admin_app_url}/certificate?courseId=${row.course_id}&userId=${encodeURIComponent(user?.user_id || user?.learner_phone || '')}`}
                                          target="_blank"
                                          rel="noreferrer"
                                          disabled={!(row.course_id && (user?.user_id || user?.learner_phone))}
                                          sx={{ border: '1px solid', borderColor: 'divider' }}
                                        >
                                          <CertificateIcon fontSize="small" />
                                        </IconButton>
                                      </span>
                                    </Tooltip>
                                  </TableCell>
                                </TableRow>
                              ))}
                              {certificateRows.length === 0 && (
                                <TableRow>
                                  <TableCell colSpan={5}>
                                    <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                                      No certificates found for this user.
                                    </Typography>
                                  </TableCell>
                                </TableRow>
                              )}
                            </TableBody>
                          </Table>
                        </TableContainer>
                      </Paper>
                    </Grid>
                    <Grid item xs={12}>
                      <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                        <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1 }}>
                          <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                            User Data
                          </Typography>
                          <Chip size="small" label={`${userDataRows.length} rows`} />
                        </Stack>
                        <Divider sx={{ mb: 1.5 }} />
                        <TableContainer component={Box}>
                          <Table>
                            <TableHead>
                              <TableRow>
                                <TableCell>Language</TableCell>
                                <TableCell>VIP</TableCell>
                                <TableCell>Diamond</TableCell>
                                <TableCell>First Join</TableCell>
                                <TableCell>Last Active</TableCell>
                              </TableRow>
                            </TableHead>
                            <TableBody>
                              {userDataRows.map((row) => (
                                <TableRow key={`profile-ud-${row.id}`}>
                                  <TableCell>
                                    <Stack direction="row" spacing={1} alignItems="center">
                                      <Avatar src={buildImageUrl(row.languageImagePath)} sx={{ width: 26, height: 26, bgcolor: 'action.selected' }}>
                                        {row.languageLabel ? row.languageLabel.charAt(0) : 'L'}
                                      </Avatar>
                                      <Box>
                                        <Typography variant="body2" sx={{ fontWeight: 700 }}>
                                          {row.languageLabel || '-'}
                                        </Typography>
                                        <Typography variant="caption" color="text.secondary">
                                          {row.major || '-'}
                                        </Typography>
                                      </Box>
                                    </Stack>
                                  </TableCell>
                                  <TableCell>{row.isVip ? <Chip size="small" color="warning" label="VIP" /> : <Chip size="small" label="No" />}</TableCell>
                                  <TableCell>{row.diamondPlan ? <Chip size="small" color="primary" label="Yes" /> : <Chip size="small" label="No" />}</TableCell>
                                  <TableCell>{row.firstJoin ? String(row.firstJoin) : '-'}</TableCell>
                                  <TableCell>{row.lastActive ? String(row.lastActive) : '-'}</TableCell>
                                </TableRow>
                              ))}
                              {userDataRows.length === 0 && (
                                <TableRow>
                                  <TableCell colSpan={5}>
                                    <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                                      No user_data rows found.
                                    </Typography>
                                  </TableCell>
                                </TableRow>
                              )}
                            </TableBody>
                          </Table>
                        </TableContainer>
                      </Paper>
                    </Grid>
                    <Grid item xs={12}>
                      <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                        <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1 }}>
                          <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                            Purchased Courses
                          </Typography>
                          <Chip size="small" label={`${vipCoursesRows.length} courses`} />
                        </Stack>
                        <Divider sx={{ mb: 1.5 }} />
                        <TableContainer component={Box}>
                          <Table>
                            <TableHead>
                              <TableRow>
                                <TableCell>Language</TableCell>
                                <TableCell>Course</TableCell>
                                <TableCell>Status</TableCell>
                              </TableRow>
                            </TableHead>
                            <TableBody>
                              {vipCoursesRows.map((courseRow) => (
                                <TableRow key={`purchase-${courseRow.course_id}`}>
                                  <TableCell>
                                    <Stack direction="row" spacing={1} alignItems="center">
                                      <Avatar src={buildImageUrl(courseRow.languageImagePath)} sx={{ width: 26, height: 26, bgcolor: 'action.selected' }}>
                                        {courseRow.languageLabel ? courseRow.languageLabel.charAt(0) : 'L'}
                                      </Avatar>
                                      <Box>
                                        <Typography variant="body2" sx={{ fontWeight: 700 }}>
                                          {courseRow.languageLabel || courseRow.major || '-'}
                                        </Typography>
                                        <Typography variant="caption" color="text.secondary">
                                          {courseRow.major || '-'}
                                        </Typography>
                                      </Box>
                                    </Stack>
                                  </TableCell>
                                  <TableCell>
                                    <Typography variant="body2" sx={{ fontWeight: 700 }}>
                                      {courseRow.title || `Course ${courseRow.course_id}`}
                                    </Typography>
                                    <Typography variant="caption" color="text.secondary">
                                      ID: {courseRow.course_id}
                                    </Typography>
                                  </TableCell>
                                  <TableCell>
                                    <Stack direction="row" spacing={0.5} useFlexGap flexWrap="wrap">
                                      {courseRow.active === 1 ? <Chip size="small" color="success" label="Active" /> : <Chip size="small" label="Inactive" />}
                                      {courseRow.is_vip === 1 ? <Chip size="small" color="warning" label="VIP" /> : <Chip size="small" label="Free" />}
                                    </Stack>
                                  </TableCell>
                                </TableRow>
                              ))}
                              {vipCoursesRows.length === 0 && (
                                <TableRow>
                                  <TableCell colSpan={3}>
                                    <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                                      No purchased courses found for this user.
                                    </Typography>
                                  </TableCell>
                                </TableRow>
                              )}
                            </TableBody>
                          </Table>
                        </TableContainer>
                      </Paper>
                    </Grid>
                  </Grid>
                </Paper>
              )}

              {isVipAccess && (
                <>
                  <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                    <Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 1 }}>
                      VIP Access Management
                    </Typography>
                    <Stack spacing={2}>
                      {Object.entries(groupedCourses).map(([lang, courses]) => {
                        const langState = vipAccessByLanguage[lang] || { vipAccess: false, diamondPlan: false, amount: '', partnerCode: '', selectedCourseIds: [] };
                        const langErrors = vipErrorsByLang[lang] || {};
                        const isExpanded = expandedLangs.has(lang);
                        return (
                          <Paper key={`vip-lang-form-${lang}`} variant="outlined" sx={{ p: 1.75, borderRadius: 2, '&:hover': { borderColor: 'primary.light' } }}>
                            <Stack spacing={1.25}>
                              <Stack direction="row" spacing={1} alignItems="center">
                                <Avatar src={buildImageUrl(courses[0]?.languageImagePath)} sx={{ width: 28, height: 28, bgcolor: 'action.selected' }}>
                                  {lang.charAt(0)}
                                </Avatar>
                                <Typography variant="subtitle2" sx={{ fontWeight: 700 }}>{lang}</Typography>
                                <Box sx={{ flexGrow: 1, display: 'flex', alignItems: 'center', gap: 0.75, ml: 1 }}>
                                  <Chip size="small" label={`Selected ${langState.selectedCourseIds?.length || 0}`} />
                                  <Chip size="small" label={`Total ${courses.length}`} />
                                </Box>
                                <Button
                                  size="small"
                                  onClick={() => toggleLangCollapse(lang)}
                                  endIcon={<ExpandMoreIcon sx={{ transition: 'transform 0.2s', transform: isExpanded ? 'rotate(180deg)' : 'rotate(0deg)' }} />}
                                  sx={{ minWidth: 0, px: 1 }}
                                >
                                  {isExpanded ? 'COLLAPSE' : 'EXPAND'}
                                </Button>
                              </Stack>
                              <Collapse in={isExpanded} timeout="auto" unmountOnExit>
                              <TableContainer component={Box} sx={{ mb: 0.5 }}>
                                <Table size="small" sx={{ '& td': { border: 0, py: 0.5, px: 0.5 } }}>
                                  <TableBody>
                                    {Array.from({ length: Math.ceil(courses.length / 3) }).map((_, rowIndex) => {
                                      const rowSlice = courses.slice(rowIndex * 3, rowIndex * 3 + 3);
                                      return (
                                        <TableRow key={`vip-lang-row-${lang}-${rowIndex}`}>
                                          {rowSlice.map((c) => {
                                            const checked = (langState.selectedCourseIds || []).includes(c.course_id);
                                            return (
                                              <TableCell key={`vip-lang-cell-${lang}-${c.course_id}`}>
                                                <FormControlLabel
                                                  sx={{ m: 0 }}
                                                  control={
                                                    <Checkbox
                                                      checked={checked}
                                                      onChange={(e) => toggleCourseSelection(lang, c.course_id, e.target.checked)}
                                                      size="small"
                                                    />
                                                  }
                                                  label={
                                                    <Typography variant="body2" sx={{ fontWeight: 600, lineHeight: 1.25 }} noWrap>
                                                      {c.title}
                                                    </Typography>
                                                  }
                                                />
                                              </TableCell>
                                            );
                                          })}
                                          {Array.from({ length: 3 - rowSlice.length }).map((_, i) => (
                                            <TableCell key={`vip-lang-empty-${lang}-${rowIndex}-${i}`} />
                                          ))}
                                        </TableRow>
                                      );
                                    })}
                                  </TableBody>
                                </Table>
                              </TableContainer>

                              <Stack direction={{ xs: 'column', sm: 'row' }} spacing={0.5} sx={{ pt: 0.5 }}>
                                <FormControlLabel
                                  sx={{ m: 0 }}
                                  control={
                                    <Checkbox
                                      checked={Boolean(langState.vipAccess)}
                                      onChange={(e) => updateVipLanguage(lang, { vipAccess: e.target.checked })}
                                      size="small"
                                    />
                                  }
                                  label="VIP access"
                                />
                                <FormControlLabel
                                  sx={{ m: 0 }}
                                  control={
                                    <Checkbox
                                      checked={Boolean(langState.diamondPlan)}
                                      onChange={(e) => updateVipLanguage(lang, { diamondPlan: e.target.checked })}
                                      size="small"
                                    />
                                  }
                                  label="Diamond"
                                />
                              </Stack>

                              <Grid container spacing={0.75} alignItems="center">
                                <Grid item xs={12} md={4}>
                                  <TextField
                                    fullWidth
                                    size="small"
                                    label="Partner Code (Optional)"
                                    value={langState.partnerCode}
                                    onChange={(e) => updateVipLanguage(lang, { partnerCode: e.target.value })}
                                    error={Boolean(langErrors.partner_code)}
                                    helperText={langErrors.partner_code}
                                  />
                                </Grid>
                                <Grid item xs={12} md>
                                  <TextField
                                    fullWidth
                                    size="small"
                                    type="number"
                                    label="Amount (Optional)"
                                    value={langState.amount}
                                    onChange={(e) => updateVipLanguage(lang, { amount: e.target.value })}
                                    inputProps={{ min: 0 }}
                                    error={Boolean(langErrors.amount)}
                                    helperText={langErrors.amount}
                                  />
                                </Grid>
                                <Grid item xs={12} md="auto" sx={{ display: 'flex', justifyContent: { xs: 'flex-start', md: 'flex-end' } }}>
                                  <Button
                                    variant="contained"
                                    size="small"
                                    disabled={vipSavingLang === lang}
                                    onClick={() => saveVipLanguage(lang, courses)}
                                  >
                                    Save
                                  </Button>
                                </Grid>
                              </Grid>
                              </Collapse>
                            </Stack>
                          </Paper>
                        );
                      })}
                    </Stack>
                  </Paper>
                </>
              )}

              {isPush && (
                <Stack spacing={1.5}>
                  <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                    <Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 0.5 }}>
                      Push Notification
                    </Typography>
                    <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
                      Compose a push message for this user. Sending is not implemented yet.
                    </Typography>
                    <Divider sx={{ mb: 2 }} />
                    <Stack spacing={1.25}>
                      <TextField
                        fullWidth
                        label="Title"
                        value={pushForm.title}
                        onChange={(e) => setPushForm((prev) => ({ ...prev, title: e.target.value }))}
                        error={Boolean(pushErrors.title)}
                        helperText={pushErrors.title}
                      />
                      <TextField
                        fullWidth
                        multiline
                        minRows={6}
                        label="Body"
                        value={pushForm.body}
                        onChange={(e) => setPushForm((prev) => ({ ...prev, body: e.target.value }))}
                        error={Boolean(pushErrors.body)}
                        helperText={pushErrors.body}
                      />
                      <Stack direction={{ xs: 'column', md: 'row' }} spacing={1.25} alignItems={{ xs: 'stretch', md: 'center' }}>
                        <TextField
                          select
                          label="Language"
                          value={pushForm.major}
                          onChange={(e) => setPushForm((prev) => ({ ...prev, major: e.target.value }))}
                          sx={{ width: { xs: '100%', md: 240 } }}
                          error={Boolean(pushErrors.major)}
                          helperText={pushErrors.major}
                        >
                          <MenuItem value="all">All</MenuItem>
                          {pushMajorOptions.map((opt) => (
                            <MenuItem key={`push-lang-${opt.major}`} value={opt.major}>
                              {opt.label}
                            </MenuItem>
                          ))}
                        </TextField>
                        <TextField
                          select
                          label="Platform"
                          value={pushForm.platform}
                          onChange={(e) => setPushForm((prev) => ({ ...prev, platform: e.target.value }))}
                          sx={{ width: { xs: '100%', md: 200 } }}
                          error={Boolean(pushErrors.platform)}
                          helperText={pushErrors.platform}
                        >
                          {pushPlatformOptions.map((value) => (
                            <MenuItem key={`push-platform-${value}`} value={value}>
                              {value === 'all' ? 'All' : value === 'android' ? 'Android' : 'iOS'}
                            </MenuItem>
                          ))}
                        </TextField>
                        <Box sx={{ flexGrow: 1 }} />
                        <Button
                          variant="contained"
                          disabled={pushSending || !pushForm.title || !pushForm.body}
                          onClick={sendPush}
                          sx={{ width: { xs: '100%', md: 'auto' }, alignSelf: { xs: 'stretch', md: 'center' } }}
                        >
                          Send
                        </Button>
                      </Stack>
                    </Stack>
                  </Paper>
                </Stack>
              )}

              {isEmail && (
                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                  <Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 0.5 }}>
                    Email
                  </Typography>
                  <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
                    Send an email to this user.
                  </Typography>
                  <Divider sx={{ mb: 2 }} />
                  <Stack spacing={1.25}>
                    {emailErrors.email && <Alert severity="error">{emailErrors.email}</Alert>}
                    {!user?.learner_email && (
                      <Alert severity="warning">
                        No email address found for this user. Email sending is disabled.
                      </Alert>
                    )}
                    <Typography variant="body2" color="text.secondary">
                      To: {user?.learner_email || '-'}
                    </Typography>
                    <TextField
                      fullWidth
                      label="Title"
                      value={emailForm.title}
                      onChange={(e) => setEmailForm((prev) => ({ ...prev, title: e.target.value }))}
                      disabled={!user?.learner_email || emailSending}
                      error={Boolean(emailErrors.title)}
                      helperText={emailErrors.title}
                    />
                    <TextField
                      fullWidth
                      multiline
                      minRows={8}
                      label="Body"
                      value={emailForm.body}
                      onChange={(e) => setEmailForm((prev) => ({ ...prev, body: e.target.value }))}
                      disabled={!user?.learner_email || emailSending}
                      error={Boolean(emailErrors.body)}
                      helperText={emailErrors.body}
                    />
                    <Stack direction="row" justifyContent="flex-end">
                      <Button
                        variant="contained"
                        disabled={!user?.learner_email || emailSending || !emailForm.title || !emailForm.body}
                        onClick={sendEmail}
                      >
                        Send
                      </Button>
                    </Stack>
                  </Stack>
                </Paper>
              )}

              {isSecurity && (
                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                  <Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 0.5 }}>
                    Account Security
                  </Typography>
                  <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
                    Change password for this user.
                  </Typography>
                  <Divider sx={{ mb: 2 }} />
                  <Box component="form" onSubmit={handleSavePassword}>
                    <TextField
                      fullWidth
                      type="password"
                      label="New Password"
                      value={securityForm.data.password}
                      onChange={(e) => securityForm.setData('password', e.target.value)}
                      error={Boolean(securityForm.errors.password)}
                      helperText={securityForm.errors.password || 'Minimum 8 characters'}
                    />
                    <Stack direction="row" justifyContent="flex-end" sx={{ mt: 2 }}>
                      <Button type="submit" variant="contained" disabled={securityForm.processing || !securityForm.data.password}>
                        Update Password
                      </Button>
                    </Stack>
                  </Box>
                </Paper>
              )}
            </Stack>

            <Paper
              variant="outlined"
              sx={{
                borderRadius: 2,
                overflow: 'hidden',
                alignSelf: { xs: 'stretch', md: 'start' },
                position: { md: 'sticky' },
                top: { md: 72 },
              }}
            >
              <Box sx={{ p: 2, borderBottom: '1px solid', borderColor: 'divider' }}>
                <Box sx={{ position: 'relative', pb: 2.75 }}>
                  <Box sx={{ height: 110, borderRadius: 1.5, bgcolor: 'action.hover', overflow: 'hidden', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                    {userCoverUrl ? (
                      <Box component="img" src={userCoverUrl} alt={user?.learner_name || 'User'} sx={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                    ) : (
                      <PersonIcon color="action" />
                    )}
                  </Box>
                  <Box sx={{ position: 'absolute', left: 12, bottom: 0, transform: 'translateY(-10%)', width: 44, height: 44, borderRadius: '50%', bgcolor: 'background.paper', border: '2px solid', borderColor: 'background.paper', overflow: 'hidden', display: 'flex', alignItems: 'center', justifyContent: 'center', boxShadow: 2 }}>
                    {userImageUrl ? (
                      <Box component="img" src={userImageUrl} alt={user?.learner_name || 'User'} sx={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                    ) : (
                      <PersonIcon sx={{ fontSize: 18, color: 'text.disabled' }} />
                    )}
                  </Box>
                </Box>
                <Typography variant="subtitle2" sx={{ fontWeight: 700, mt: 1.25 }} noWrap>
                  {user?.learner_name || `User ${user?.user_id || ''}`}
                </Typography>
                <Stack direction="row" spacing={0.5} alignItems="center" sx={{ mt: 0.35, flexWrap: 'wrap' }}>
                  <Chip size="small" label={`ID ${user.user_id}`} />
                  {user.email_verified_at ? <Chip size="small" color="success" label="Verified" /> : <Chip size="small" color="warning" label="Unverified" />}
                  {hasDiamondPlan && <Chip size="small" color="primary" label="Diamond" />}
                  {hasAnyVip && <Chip size="small" color="warning" label="VIP" />}
                </Stack>
                <Typography variant="caption" color="text.secondary" sx={{ mt: 0.65, display: 'block' }} noWrap>
                  {user?.learner_email || '-'}
                </Typography>
                <Typography variant="caption" color="text.secondary" sx={{ display: 'block' }}>
                  {user?.learner_phone || '-'}
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

        <Snackbar
          open={openSnackbar || Boolean(flash?.success)}
          autoHideDuration={3000}
          onClose={() => setOpenSnackbar(false)}
          anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
        >
          <Alert severity="success" variant="filled" onClose={() => setOpenSnackbar(false)}>
            {flash?.success || 'Saved successfully.'}
          </Alert>
        </Snackbar>
      </Box>
    </ThemeProvider>
  );
}

UserEdit.layout = (page) => <AdminLayout children={page} />;
