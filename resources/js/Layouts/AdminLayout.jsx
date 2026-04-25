import React, { useEffect, useMemo, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import {
  Box,
  Badge,
  Drawer,
  AppBar,
  Toolbar,
  List,
  Typography,
  Divider,
  IconButton,
  ListItem,
  ListItemButton,
  ListItemIcon,
  ListItemText,
  CssBaseline,
  ThemeProvider,
  createTheme,
  Tooltip,
  Menu,
  MenuItem,
  Button,
  Avatar,
  Stack,
  Chip,
} from '@mui/material';
import {
  Menu as MenuIcon,
  Dashboard as DashboardIcon,
  People as PeopleIcon,
  Person as PersonOutlineIcon,
  LibraryBooks as LessonsIcon,
  School as CoursesIcon,
  MusicNote as SongsIcon,
  Settings as SettingsIcon,
  AutoAwesomeMosaic as UiShowcaseIcon,
  AccountCircle,
  DarkMode as DarkModeIcon,
  LightMode as LightModeIcon,
  Logout as LogoutIcon,
  Person as PersonIcon,
  Payments as FinancialIcon,
  AdminPanelSettings as AdministrationIcon,
  Language as LanguageIcon,
  Folder as ResourcesIcon,
  BarChart as BarChartIcon,
  Star as StarIcon,
  Notifications as NotificationsIcon,
  Email as EmailIcon,
  ChatBubbleOutline as ChatBubbleOutlineIcon,
  Forum as ForumIcon,
  HelpOutline as HelpOutlineIcon,
  Campaign as AnnouncementIcon,
  Apps as AppsIcon,
  Extension as MiniProgramIcon,
  Groups as CommunitiesIcon,
} from '@mui/icons-material';

const compactDrawerWidth = 208;

export default function AdminLayout({ children }) {
  const { url } = usePage();
  const { admin_app_url, auth, admin_counters } = usePage().props;
  const admin = auth.admin;
  const unactivatedPayments = Number(admin_counters?.unactivated_payments ?? 0);
  const [supportChatUnread, setSupportChatUnread] = useState(Number(admin_counters?.support_chat_unread ?? 0));
  const [adminNotificationsUnread, setAdminNotificationsUnread] = useState(Number(admin_counters?.admin_notifications_unread ?? 0));
  const [open, setOpen] = useState(true);
  const [isDarkMode, setIsDarkMode] = useState(() => {
    if (typeof window === 'undefined') {
      return false;
    }
    return window.localStorage.getItem('admin-color-mode') === 'dark';
  });

  // Profile Menu State
  const [anchorEl, setAnchorEl] = useState(null);
  const openProfileMenu = Boolean(anchorEl);
  const handleProfileMenuOpen = (event) => setAnchorEl(event.currentTarget);
  const handleProfileMenuClose = () => setAnchorEl(null);
  const handleSignOut = () => {
    handleProfileMenuClose();
    router.post(`${admin_app_url}/logout`);
  };

  const toggleDrawer = () => {
    setOpen(!open);
  };
  const toggleColorMode = () => {
    setIsDarkMode((prev) => !prev);
  };

  useEffect(() => {
    if (typeof window !== 'undefined') {
      window.localStorage.setItem('admin-color-mode', isDarkMode ? 'dark' : 'light');
    }
  }, [isDarkMode]);

  useEffect(() => {
    setSupportChatUnread(Number(admin_counters?.support_chat_unread ?? 0));
  }, [admin_counters?.support_chat_unread]);

  useEffect(() => {
    setAdminNotificationsUnread(Number(admin_counters?.admin_notifications_unread ?? 0));
  }, [admin_counters?.admin_notifications_unread]);

  useEffect(() => {
    if (!admin) {
      return undefined;
    }

    const poll = () => {
      if (document.visibilityState && document.visibilityState !== 'visible') {
        return;
      }
      if (!window.axios) {
        return;
      }
      const pathname = window.location.pathname || '';
      const idx = pathname.toLowerCase().indexOf('/admin');
      const base = idx >= 0 ? `${window.location.origin}${pathname.slice(0, idx)}/admin` : admin_app_url;
      const root = base.replace(/\/$/, '');
      window.axios
        .get(`${root}/support-chat/unread-count`)
        .then((res) => setSupportChatUnread(Number(res.data?.unread ?? 0)))
        .catch(() => {});
      window.axios
        .get(`${root}/notifications/unread-count`)
        .then((res) => setAdminNotificationsUnread(Number(res.data?.unread ?? 0)))
        .catch(() => {});
    };

    poll();
    const timer = window.setInterval(poll, 5000);
    return () => window.clearInterval(timer);
  }, [admin_app_url, admin]);

  const theme = useMemo(
    () =>
      createTheme({
        palette: {
          mode: isDarkMode ? 'dark' : 'light',
          primary: {
            main: '#3b82f6',
          },
          background: {
            default: isDarkMode ? '#0f172a' : '#f3f4f6',
            paper: isDarkMode ? '#111827' : '#ffffff',
          },
        },
        shape: {
          borderRadius: 8,
        },
        components: {
          MuiPaper: {
            defaultProps: {
              elevation: 0,
            },
            styleOverrides: {
              root: {
                border: '1px solid',
                borderColor: isDarkMode ? 'rgba(148, 163, 184, 0.18)' : 'rgba(15, 23, 42, 0.08)',
              },
            },
          },
        },
      }),
    [isDarkMode]
  );

  const navigationGroups = [
    {
      title: 'Main',
      items: [
        { name: 'Dashboard', href: `${admin_app_url}/dashboard`, icon: <DashboardIcon /> },
      ]
    },
    {
      title: 'User Management',
      items: [
        { name: 'User List', href: `${admin_app_url}/users`, icon: <PeopleIcon /> },
        { name: 'Analysis', href: `${admin_app_url}/users/analysis`, icon: <BarChartIcon /> },
        { name: 'Enroll Course', href: `${admin_app_url}/users/enroll-course`, icon: <FinancialIcon /> },
        { name: 'VIP Transfer', href: `${admin_app_url}/users/vip-transfer`, icon: <StarIcon /> },
        { name: 'Push Notification', href: `${admin_app_url}/users/push`, icon: <NotificationsIcon /> },
        { name: 'Email', href: `${admin_app_url}/users/email`, icon: <EmailIcon /> },
        { name: 'Notifications', href: `${admin_app_url}/notifications`, icon: <NotificationsIcon /> },
        { name: 'Support Chat', href: `${admin_app_url}/support-chat`, icon: <ChatBubbleOutlineIcon /> },
        { name: 'FAQs', href: `${admin_app_url}/faqs`, icon: <HelpOutlineIcon /> },
      ]
    },
    {
      title: 'Content',
      items: [
        { name: 'Courses', href: `${admin_app_url}/courses`, icon: <CoursesIcon /> },
        { name: 'Additional Lessons', href: `${admin_app_url}/additional-lessons`, icon: <LessonsIcon /> },
        { name: 'Songs', href: `${admin_app_url}/songs`, icon: <SongsIcon /> },
        { name: 'Teachers', href: `${admin_app_url}/teachers`, icon: <PersonOutlineIcon /> },
        { name: 'Resources', href: `${admin_app_url}/resources`, icon: <ResourcesIcon /> },
        { name: 'Discussions', href: `${admin_app_url}/discussions`, icon: <ForumIcon /> },
      ]
    },
    {
      title: 'Finance',
      items: [
        { name: 'Financial', href: `${admin_app_url}/financial`, icon: <FinancialIcon /> },
      ]
    },
    {
      title: 'System',
      items: [
        { name: 'Save Replies', href: `${admin_app_url}/save-replies`, icon: <ChatBubbleOutlineIcon /> },
        { name: 'Activity Logs', href: `${admin_app_url}/activity-logs`, icon: <BarChartIcon /> },
        { name: 'Administration', href: `${admin_app_url}/administration`, icon: <AdministrationIcon /> },
        { name: 'Communities', href: `${admin_app_url}/communities`, icon: <CommunitiesIcon /> },
        { name: 'Language Management', href: `${admin_app_url}/languages`, icon: <LanguageIcon /> },
        { name: 'Payment Methods', href: `${admin_app_url}/payment-methods`, icon: <FinancialIcon /> },
        { name: 'Package Plans', href: `${admin_app_url}/package-plans`, icon: <SettingsIcon /> },
        { name: 'Announcements', href: `${admin_app_url}/announcements`, icon: <AnnouncementIcon /> },
        { name: 'Applications', href: `${admin_app_url}/apps`, icon: <AppsIcon /> },
        { name: 'Mini Programs', href: `${admin_app_url}/mini-programs`, icon: <MiniProgramIcon /> },
        { name: 'UI Showcase', href: `${admin_app_url}/ui-showcase`, icon: <UiShowcaseIcon /> },
      ]
    }
  ];

  const adminAccess = Array.isArray(admin?.access) ? admin.access : [];
  const hasSectorAccess = (sector) => adminAccess.includes('*') || adminAccess.includes(sector);
  const visibleNavigationGroups = navigationGroups
    .map((group) => {
      const items = group.items.filter((item) => {
        if (
          item.href.endsWith('/users') ||
          item.href.endsWith('/analysis') ||
          item.href.endsWith('/enroll-course') ||
          item.href.endsWith('/users/vip-transfer') ||
          item.href.endsWith('/users/push') ||
          item.href.endsWith('/users/email') ||
          item.href.endsWith('/notifications') ||
          item.href.endsWith('/support-chat') ||
          item.href.endsWith('/faqs')
        ) {
          return hasSectorAccess('user');
        }
        if (item.href.endsWith('/courses')) {
          return hasSectorAccess('administration') || hasSectorAccess('course');
        }
        if (item.href.endsWith('/additional-lessons')) {
          return hasSectorAccess('administration') || hasSectorAccess('course');
        }
        if (item.href.endsWith('/songs')) {
          return hasSectorAccess('administration') || hasSectorAccess('course');
        }
        if (item.href.endsWith('/resources')) {
          return hasSectorAccess('administration') || hasSectorAccess('course');
        }
        if (
          item.href.endsWith('/teachers') ||
          item.href.endsWith('/communities') ||
          item.href.endsWith('/activity-logs') ||
          item.href.endsWith('/administration') ||
          item.href.endsWith('/languages') ||
          item.href.endsWith('/payment-methods') ||
          item.href.endsWith('/package-plans') ||
          item.href.endsWith('/announcements') ||
          item.href.endsWith('/apps') ||
          item.href.endsWith('/mini-programs')
        ) {
          return hasSectorAccess('administration');
        }
        if (item.href.endsWith('/financial')) {
          return hasSectorAccess('administration');
        }
        return true;
      });

      if (items.length === 0) {
        return null;
      }

      return {
        ...group,
        items,
      };
    })
    .filter(Boolean);

  const isPathActive = (href) => {
    try {
      // Get the path from the href (which might be a full URL)
      const hrefPath = new URL(href).pathname;
      // Get the current path from Inertia's url (which might include query params)
      const currentPath = url.split('?')[0];

      // Exact match for Dashboard and Analysis
      if (hrefPath.endsWith('/dashboard') || hrefPath.endsWith('/analysis')) {
        return currentPath === hrefPath;
      }
      
      // Prefix match for others (e.g. /admin/users matches /admin/users/1)
      // but exclude analysis from /admin/users match
      if (
        hrefPath.endsWith('/users') &&
        (currentPath.includes('/analysis') ||
          currentPath.includes('/enroll-course') ||
          currentPath.includes('/vip-transfer') ||
          currentPath.includes('/push') ||
          currentPath.includes('/email'))
      ) {
        return false;
      }

      return currentPath.startsWith(hrefPath);
    } catch (e) {
      // Fallback to simple string comparison if URL parsing fails
      return url === href;
    }
  };

  const currentNav = visibleNavigationGroups.flatMap((g) => g.items).find((item) => isPathActive(item.href));

  return (
    <ThemeProvider theme={theme}>
      <Box sx={{ display: 'flex', color: 'text.primary' }}>
      <CssBaseline />
      <AppBar
        position="fixed"
        elevation={0}
        color="default"
        sx={{
          zIndex: (theme) => theme.zIndex.drawer + 1,
          bgcolor: 'background.paper',
          borderBottom: 1,
          borderColor: 'divider',
          transition: (theme) =>
            theme.transitions.create(['width', 'margin'], {
              easing: theme.transitions.easing.sharp,
              duration: theme.transitions.duration.leavingScreen,
            }),
          ...(open && {
            marginLeft: compactDrawerWidth,
            width: `calc(100% - ${compactDrawerWidth}px)`,
            transition: (theme) =>
              theme.transitions.create(['width', 'margin'], {
                easing: theme.transitions.easing.sharp,
                duration: theme.transitions.duration.enteringScreen,
              }),
          }),
        }}
      >
        <Toolbar variant="dense" sx={{ minHeight: 48 }}>
          <IconButton
            color="default"
            aria-label="open drawer"
            onClick={toggleDrawer}
            edge="start"
            sx={{
              mr: 1,
              ...(open && { display: 'none' }),
            }}
          >
            <MenuIcon />
          </IconButton>
          <Typography variant="subtitle1" noWrap component="div" sx={{ flexGrow: 1, fontWeight: 600 }}>
            {currentNav ? currentNav.name : 'Admin Panel'}
          </Typography>
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.5 }}>
            <Tooltip title={isDarkMode ? 'Switch to light mode' : 'Switch to dark mode'}>
              <IconButton 
                color="default" 
                size="small" 
                onClick={toggleColorMode} 
                sx={{ 
                  width: 32, 
                  height: 32,
                  bgcolor: isDarkMode ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.03)',
                  '&:hover': {
                    bgcolor: isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.06)',
                  }
                }}
              >
                {isDarkMode ? <LightModeIcon sx={{ fontSize: 18 }} /> : <DarkModeIcon sx={{ fontSize: 18 }} />}
              </IconButton>
            </Tooltip>
            <Tooltip title="Notifications">
              <IconButton
                component={Link}
                href={`${admin_app_url}/notifications`}
                color="default"
                size="small"
                sx={{
                  width: 32,
                  height: 32,
                  bgcolor: isDarkMode ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.03)',
                  '&:hover': {
                    bgcolor: isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.06)',
                  },
                }}
              >
                <Badge color="error" badgeContent={adminNotificationsUnread} max={99}>
                  <NotificationsIcon sx={{ fontSize: 18 }} />
                </Badge>
              </IconButton>
            </Tooltip>
            <Tooltip title="Support Chat">
              <IconButton
                component={Link}
                href={`${admin_app_url}/support-chat`}
                color="default"
                size="small"
                sx={{
                  width: 32,
                  height: 32,
                  bgcolor: isDarkMode ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.03)',
                  '&:hover': {
                    bgcolor: isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.06)',
                  },
                }}
              >
                <Badge color="error" badgeContent={supportChatUnread} max={99}>
                  <ChatBubbleOutlineIcon sx={{ fontSize: 18 }} />
                </Badge>
              </IconButton>
            </Tooltip>
            <Box sx={{ display: 'flex', alignItems: 'center', ml: 1 }}>
              <Typography variant="caption" sx={{ mr: 1.25, color: 'text.secondary', fontWeight: 600 }}>
                {admin?.name || 'Admin User'}
              </Typography>
              <IconButton
                color="default"
                size="small"
                onClick={handleProfileMenuOpen}
                aria-controls={openProfileMenu ? 'profile-menu' : undefined}
                aria-haspopup="true"
                aria-expanded={openProfileMenu ? 'true' : undefined}
                sx={{ 
                  p: 0.25,
                  border: '1px solid',
                  borderColor: 'divider',
                  '&:hover': {
                    borderColor: 'primary.main',
                    bgcolor: 'primary.lighter'
                  }
                }}
              >
                {admin?.image_url ? (
                  <Avatar src={admin.image_url} sx={{ width: 26, height: 28 }} />
                ) : (
                  <AccountCircle sx={{ fontSize: 24 }} />
                )}
              </IconButton>
            </Box>
            <Menu
              id="profile-menu"
              anchorEl={anchorEl}
              open={openProfileMenu}
              onClose={handleProfileMenuClose}
              onClick={handleProfileMenuClose}
              PaperProps={{
                sx: {
                  mt: 1.5,
                  minWidth: 180,
                  boxShadow: '0px 4px 20px rgba(0,0,0,0.1)',
                },
              }}
              transformOrigin={{ horizontal: 'right', vertical: 'top' }}
              anchorOrigin={{ horizontal: 'right', vertical: 'bottom' }}
            >
              <MenuItem component={Link} href={`${admin_app_url}/profile`} sx={{ py: 1 }}>
                <ListItemIcon sx={{ minWidth: 32 }}>
                  <PersonIcon fontSize="small" />
                </ListItemIcon>
                <ListItemText primary="My Profile" primaryTypographyProps={{ variant: 'body2' }} />
              </MenuItem>
              <MenuItem component={Link} href={`${admin_app_url}/settings`} sx={{ py: 1 }}>
                <ListItemIcon sx={{ minWidth: 32 }}>
                  <SettingsIcon fontSize="small" />
                </ListItemIcon>
                <ListItemText primary="Account Settings" primaryTypographyProps={{ variant: 'body2' }} />
              </MenuItem>
              <Divider sx={{ my: 0.5 }} />
              <Box sx={{ p: 1, pb: 0.5 }}>
                <Button
                  onClick={handleSignOut}
                  fullWidth
                  variant="outlined"
                  color="error"
                  size="small"
                  startIcon={<LogoutIcon fontSize="small" />}
                  sx={{ 
                     justifyContent: 'flex-start',
                     textTransform: 'none',
                     fontWeight: 600,
                     borderRadius: 1.25,
                     borderWidth: '1.5px',
                     '&:hover': {
                       borderWidth: '1.5px',
                     }
                   }}
                >
                  Sign Out
                </Button>
              </Box>
            </Menu>
          </Box>
        </Toolbar>
      </AppBar>
      <Drawer
        variant="permanent"
        open={open}
        sx={{
          width: compactDrawerWidth,
          flexShrink: 0,
          whiteSpace: 'nowrap',
          boxSizing: 'border-box',
          ...(open && {
            '& .MuiDrawer-paper': {
              width: compactDrawerWidth,
              transition: (theme) =>
                theme.transitions.create('width', {
                  easing: theme.transitions.easing.sharp,
                  duration: theme.transitions.duration.enteringScreen,
                }),
              overflowX: 'hidden',
              borderRight: '1px solid',
              borderColor: 'divider',
              bgcolor: 'background.paper',
            },
          }),
          ...(!open && {
            '& .MuiDrawer-paper': {
              transition: (theme) =>
                theme.transitions.create('width', {
                  easing: theme.transitions.easing.sharp,
                  duration: theme.transitions.duration.leavingScreen,
                }),
              overflowX: 'hidden',
              width: (theme) => theme.spacing(6.5),
              [theme.breakpoints.up('sm')]: {
                width: (theme) => theme.spacing(7.5),
              },
              borderRight: '1px solid',
              borderColor: 'divider',
              bgcolor: 'background.paper',
            },
          }),
        }}
      >
        {/* User Info Section in Drawer */}
        <Box sx={{ 
          px: open ? 2 : 1, 
          py: 2.5, 
          minHeight: open ? 92 : 80,
          display: 'flex', 
          flexDirection: open ? 'row' : 'column',
          alignItems: 'center',
          gap: open ? 1.5 : 1,
          overflow: 'hidden'
        }}>
          <Avatar 
            src={admin?.image_url} 
            sx={{ 
              width: open ? 40 : 32, 
              height: open ? 40 : 32,
              bgcolor: 'primary.light',
              color: 'primary.main',
              fontSize: '1rem',
              transition: 'all 0.2s ease'
            }}
          >
            {admin?.name?.charAt(0) || 'A'}
          </Avatar>
          {open && (
            <Box sx={{ minWidth: 0 }}>
              <Typography variant="body2" sx={{ fontWeight: 700, noWrap: true, overflow: 'hidden', textOverflow: 'ellipsis' }}>
                {admin?.name}
              </Typography>
              <Typography variant="caption" color="text.secondary" sx={{ noWrap: true, overflow: 'hidden', textOverflow: 'ellipsis', display: 'block' }}>
                {admin?.email}
              </Typography>
            </Box>
          )}
        </Box>
        <Divider />

        <List component="nav" dense disablePadding sx={{ py: 0.5 }}>
          {visibleNavigationGroups.map((group, groupIndex) => (
            <React.Fragment key={group.title}>
              {open && (
                <Typography
                  variant="overline"
                  sx={{
                    px: 3,
                    py: 1,
                    display: 'block',
                    fontWeight: 700,
                    color: 'text.secondary',
                    fontSize: '0.65rem',
                    letterSpacing: '0.5px',
                  }}
                >
                  {group.title}
                </Typography>
              )}
              {group.items.map((item) => (
                <ListItem key={item.name} disablePadding sx={{ display: 'block' }}>
                  <ListItemButton
                    component={Link}
                    href={item.href}
                    selected={isPathActive(item.href)}
                    sx={{
                      minHeight: 40,
                      justifyContent: open ? 'initial' : 'center',
                      px: 1.5,
                      py: 0.5,
                      mx: 1,
                      my: 0.25,
                      borderRadius: 1.25,
                      transition: 'all 0.2s ease',
                      '&.Mui-selected': {
                        bgcolor: isDarkMode ? 'rgba(59, 130, 246, 0.12)' : 'rgba(59, 130, 246, 0.08)',
                        color: 'primary.main',
                        '& .MuiListItemIcon-root': {
                          color: 'primary.main',
                        },
                        '& .MuiListItemText-primary': {
                          fontWeight: 700,
                        },
                      },
                      '&:hover': {
                        bgcolor: isDarkMode ? 'rgba(255, 255, 255, 0.04)' : 'rgba(0, 0, 0, 0.03)',
                      },
                      '&.Mui-selected:hover': {
                        bgcolor: isDarkMode ? 'rgba(59, 130, 246, 0.18)' : 'rgba(59, 130, 246, 0.12)',
                      },
                    }}
                  >
                    <ListItemIcon
                      sx={{
                        minWidth: 0,
                        mr: open ? 1.5 : 'auto',
                        color: 'text.secondary',
                        transition: 'all 0.2s ease',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        width: 32,
                        height: 32,
                        borderRadius: 1,
                        '& svg': { fontSize: 20 },
                        '.Mui-selected &': {
                          color: 'primary.main',
                          bgcolor: isDarkMode ? 'rgba(59, 130, 246, 0.15)' : 'rgba(59, 130, 246, 0.1)',
                        },
                      }}
                    >
                      {item.href.endsWith('/support-chat') ? (
                        open ? (
                          item.icon
                        ) : (
                          <Badge
                            color="error"
                            overlap="circular"
                            badgeContent={supportChatUnread}
                            invisible={supportChatUnread <= 0}
                            max={99}
                          >
                            {item.icon}
                          </Badge>
                        )
                      ) : (
                        item.icon
                      )}
                    </ListItemIcon>
                    <ListItemText
                      primary={item.name}
                      sx={{ 
                        opacity: open ? 1 : 0,
                        '& .MuiListItemText-primary': {
                          fontSize: '0.8125rem',
                          letterSpacing: '0.1px',
                        }
                      }}
                    />
                    {open && item.href.endsWith('/users/enroll-course') && unactivatedPayments > 0 ? (
                      <Chip
                        label={unactivatedPayments}
                        color="error"
                        size="small"
                        sx={{
                          ml: 'auto',
                          height: 20,
                          '& .MuiChip-label': { px: 0.75, fontWeight: 700 },
                        }}
                      />
                    ) : null}
                    {open && item.href.endsWith('/support-chat') && supportChatUnread > 0 ? (
                      <Chip
                        label={supportChatUnread}
                        color="error"
                        size="small"
                        sx={{
                          ml: 'auto',
                          height: 20,
                          '& .MuiChip-label': { px: 0.75, fontWeight: 700 },
                        }}
                      />
                    ) : null}
                  </ListItemButton>
                </ListItem>
              ))}
              {groupIndex < visibleNavigationGroups.length - 1 && <Divider sx={{ my: 1, mx: 2 }} />}
            </React.Fragment>
          ))}
        </List>
      </Drawer>
      <Box
        component="main"
        sx={{
          flexGrow: 1,
          px: 2,
          pb: 2,
          pt: 8,
          boxSizing: 'border-box',
          bgcolor: 'background.default',
          minHeight: '100vh',
          '@supports (min-height: 100dvh)': {
            minHeight: '100dvh',
          },
        }}
      >
        {children}
      </Box>
      </Box>
    </ThemeProvider>
  );
}
