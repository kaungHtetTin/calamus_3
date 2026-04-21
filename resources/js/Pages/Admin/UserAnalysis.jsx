import React, { useState, useMemo } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import {
  Box,
  Typography,
  Paper,
  Stack,
  Grid,
  Card,
  CardContent,
  Chip,
  Divider,
  TextField,
  MenuItem,
  Button,
  IconButton,
  useTheme,
} from '@mui/material';
import {
  Verified as VerifiedIcon,
  People as PeopleIcon,
  Apps as AppsIcon,
  Timeline as TimelineIcon,
  Refresh as RefreshIcon,
  Download as DownloadIcon,
  MoreVert as MoreVertIcon,
} from '@mui/icons-material';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip as ChartTooltip,
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell,
  Legend,
} from 'recharts';

export default function UserAnalysis({ analytics, activityLanguageOptions, selectedActivityLanguageId }) {
  const theme = useTheme();
  const { admin_app_url } = usePage().props;
  const [activityLanguageId, setActivityLanguageId] = useState(selectedActivityLanguageId || 'all');

  const defaultAnalytics = {
    total_users: 12840,
    gender: [
      { name: 'Female', value: 6420 },
      { name: 'Male', value: 5980 },
      { name: 'Other', value: 440 },
    ],
    regions: [
      { name: 'Yangon', value: 3260 },
      { name: 'Mandalay', value: 2410 },
      { name: 'Bago', value: 1560 },
      { name: 'Naypyidaw', value: 980 },
      { name: 'Shan', value: 860 },
    ],
    verification: [
      { name: 'Verified', value: 8120 },
      { name: 'Pending', value: 3120 },
      { name: 'Unverified', value: 1600 },
    ],
    activity: [
      { name: 'Mon', value: 1240 },
      { name: 'Tue', value: 1420 },
      { name: 'Wed', value: 1360 },
      { name: 'Thu', value: 1580 },
      { name: 'Fri', value: 1710 },
      { name: 'Sat', value: 1120 },
      { name: 'Sun', value: 980 },
    ],
    new_regs_today_by_app: [],
    new_regs_last7_by_app: [],
  };

  const safeAnalytics = {
    total_users: Number.isFinite(analytics?.total_users) ? analytics.total_users : defaultAnalytics.total_users,
    gender: Array.isArray(analytics?.gender) ? analytics.gender : defaultAnalytics.gender,
    regions: Array.isArray(analytics?.regions) ? analytics.regions : defaultAnalytics.regions,
    verification: Array.isArray(analytics?.verification) ? analytics.verification : defaultAnalytics.verification,
    activity: Array.isArray(analytics?.activity) ? analytics.activity : defaultAnalytics.activity,
    app_users: Array.isArray(analytics?.app_users) ? analytics.app_users : [],
    app_activity: Array.isArray(analytics?.app_activity) ? analytics.app_activity : [],
    new_regs_today_by_app: Array.isArray(analytics?.new_regs_today_by_app) ? analytics.new_regs_today_by_app : defaultAnalytics.new_regs_today_by_app,
    new_regs_last7_by_app: Array.isArray(analytics?.new_regs_last7_by_app) ? analytics.new_regs_last7_by_app : defaultAnalytics.new_regs_last7_by_app,
  };

  const verifiedCount = safeAnalytics.verification.find((v) => v.name === 'Verified')?.value ?? 0;
  const verificationRate = safeAnalytics.total_users > 0 ? Math.round((verifiedCount / safeAnalytics.total_users) * 100) : 0;
  const dailyActiveAvg =
    safeAnalytics.activity.length > 0
      ? Math.round(safeAnalytics.activity.reduce((sum, item) => sum + (item?.value ?? 0), 0) / safeAnalytics.activity.length)
      : 0;

  const COLORS = [
    theme.palette.primary.main,
    theme.palette.success.main,
    theme.palette.warning.main,
    theme.palette.error.main,
    theme.palette.info.main,
    '#8884d8',
    '#82ca9d',
    '#ffc658'
  ];

  const handleActivityLanguageChange = (event) => {
    const nextLanguageId = event.target.value;
    setActivityLanguageId(nextLanguageId);

    router.get(
      `${admin_app_url}/users/analysis`,
      nextLanguageId !== 'all' ? { language_id: nextLanguageId } : {},
      { preserveState: true, replace: true }
    );
  };

  const newRegsLast7Keys = useMemo(() => {
    const keys = new Set();
    (safeAnalytics.new_regs_last7_by_app || []).forEach((d) => {
      Object.keys(d || {}).forEach((k) => {
        if (k !== 'name' && k !== 'date') {
          keys.add(k);
        }
      });
    });
    return Array.from(keys);
  }, [safeAnalytics.new_regs_last7_by_app]);

  return (
    <Box>
      <Head title="User Analysis" />

      <Stack spacing={1.5}>
        <Stack
          direction={{ xs: 'column', md: 'row' }}
          justifyContent="space-between"
          alignItems={{ xs: 'flex-start', md: 'center' }}
          spacing={1.5}
          sx={{ mb: 0.5 }}
        >
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              User Analysis
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Demographics, verification health, and activity snapshots.
            </Typography>
          </Box>
          <Stack direction="row" spacing={1}>
            <IconButton size="small" aria-label="refresh">
              <RefreshIcon fontSize="small" />
            </IconButton>
            <Button variant="outlined" size="small" startIcon={<DownloadIcon />}>
              Export
            </Button>
          </Stack>
        </Stack>

        <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
          <Stack
            direction={{ xs: 'column', md: 'row' }}
            alignItems={{ xs: 'stretch', md: 'center' }}
            justifyContent="space-between"
            spacing={1.25}
          >
            <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
              Filters
            </Typography>
            <Stack direction={{ xs: 'column', sm: 'row' }} spacing={1} sx={{ width: { xs: '100%', md: 'auto' } }}>
              <TextField select size="small" label="Time Range" defaultValue="30d" sx={{ minWidth: 160 }}>
                <MenuItem value="7d">Last 7 days</MenuItem>
                <MenuItem value="30d">Last 30 days</MenuItem>
                <MenuItem value="90d">Last 90 days</MenuItem>
              </TextField>
              <TextField select size="small" label="Region" defaultValue="all" sx={{ minWidth: 160 }}>
                <MenuItem value="all">All regions</MenuItem>
                {safeAnalytics.regions.slice(0, 5).map((region) => (
                  <MenuItem key={region.name} value={region.name}>
                    {region.name}
                  </MenuItem>
                ))}
              </TextField>
              <TextField select size="small" label="Verification" defaultValue="all" sx={{ minWidth: 160 }}>
                <MenuItem value="all">All</MenuItem>
                <MenuItem value="verified">Verified</MenuItem>
                <MenuItem value="pending">Pending</MenuItem>
                <MenuItem value="unverified">Unverified</MenuItem>
              </TextField>
            </Stack>
          </Stack>
        </Paper>

        <Grid container spacing={1.5}>
          <Grid item xs={12} sm={6} lg={3}>
            <Card variant="outlined" sx={{ borderRadius: 2, boxShadow: 'none' }}>
              <CardContent sx={{ p: 1.5, '&:last-child': { pb: 1.5 } }}>
                <Typography variant="body2" color="text.secondary">
                  Total Users
                </Typography>
                <Typography variant="h5" sx={{ mt: 0.5, mb: 1, fontWeight: 700, lineHeight: 1.1 }}>
                  {safeAnalytics.total_users}
                </Typography>
                <Chip size="small" label="All time" color="primary" />
              </CardContent>
            </Card>
          </Grid>
          <Grid item xs={12} sm={6} lg={3}>
            <Card variant="outlined" sx={{ borderRadius: 2, boxShadow: 'none' }}>
              <CardContent sx={{ p: 1.5, '&:last-child': { pb: 1.5 } }}>
                <Typography variant="body2" color="text.secondary">
                  Verified Users
                </Typography>
                <Typography variant="h5" sx={{ mt: 0.5, mb: 1, fontWeight: 700, lineHeight: 1.1 }}>
                  {verifiedCount}
                </Typography>
                <Chip size="small" label="+2.4%" color="success" />
              </CardContent>
            </Card>
          </Grid>
          <Grid item xs={12} sm={6} lg={3}>
            <Card variant="outlined" sx={{ borderRadius: 2, boxShadow: 'none' }}>
              <CardContent sx={{ p: 1.5, '&:last-child': { pb: 1.5 } }}>
                <Typography variant="body2" color="text.secondary">
                  Verification Rate
                </Typography>
                <Typography variant="h5" sx={{ mt: 0.5, mb: 1, fontWeight: 700, lineHeight: 1.1 }}>
                  {verificationRate}%
                </Typography>
                <Chip size="small" label="Healthy" color={verificationRate >= 60 ? 'success' : 'warning'} />
              </CardContent>
            </Card>
          </Grid>
          <Grid item xs={12} sm={6} lg={3}>
            <Card variant="outlined" sx={{ borderRadius: 2, boxShadow: 'none' }}>
              <CardContent sx={{ p: 1.5, '&:last-child': { pb: 1.5 } }}>
                <Typography variant="body2" color="text.secondary">
                  Daily Active (Avg)
                </Typography>
                <Typography variant="h5" sx={{ mt: 0.5, mb: 1, fontWeight: 700, lineHeight: 1.1 }}>
                  {dailyActiveAvg}
                </Typography>
                <Chip size="small" label="Last 7 days" color="info" />
              </CardContent>
            </Card>
          </Grid>
        </Grid>

        <Box
          sx={{
            display: 'grid',
            gridTemplateColumns: { xs: '1fr', md: 'repeat(2, minmax(0, 1fr))' },
            gap: 1.5,
          }}
        >
          <Box>
            <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
              <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1.25 }}>
                <Box>
                  <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
                    New Registers Today by App Language
                  </Typography>
                  <Typography variant="body2" color="text.secondary">
                    Count of new users who joined today by application language.
                  </Typography>
                </Box>
                <IconButton size="small" aria-label="more">
                  <MoreVertIcon fontSize="small" />
                </IconButton>
              </Stack>
              <Divider sx={{ mb: 1.5 }} />
              <Box sx={{ height: 300 }}>
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={safeAnalytics.new_regs_today_by_app}>
                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke={theme.palette.divider} />
                    <XAxis
                      dataKey="name"
                      axisLine={false}
                      tickLine={false}
                      tick={{ fontSize: 12, fill: theme.palette.text.secondary }}
                    />
                    <YAxis
                      axisLine={false}
                      tickLine={false}
                      tick={{ fontSize: 12, fill: theme.palette.text.secondary }}
                    />
                    <ChartTooltip
                      cursor={{ fill: 'transparent' }}
                      contentStyle={{
                        borderRadius: 12,
                        border: 'none',
                        boxShadow: theme.shadows[3],
                        backgroundColor: theme.palette.background.paper,
                      }}
                    />
                    <Bar dataKey="value" fill={theme.palette.primary.main} radius={[4, 4, 0, 0]} barSize={28} />
                  </BarChart>
                </ResponsiveContainer>
              </Box>
            </Paper>
          </Box>

          <Box>
            <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
              <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1.25 }}>
                <Box>
                  <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
                    New Registers (Last 7 Days) by App
                  </Typography>
                  <Typography variant="body2" color="text.secondary">
                    Daily new users split by application.
                  </Typography>
                </Box>
                <IconButton size="small" aria-label="more">
                  <MoreVertIcon fontSize="small" />
                </IconButton>
              </Stack>
              <Divider sx={{ mb: 1.5 }} />
              <Box sx={{ height: 300 }}>
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={safeAnalytics.new_regs_last7_by_app}>
                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke={theme.palette.divider} />
                    <XAxis
                      dataKey="name"
                      axisLine={false}
                      tickLine={false}
                      tick={{ fontSize: 12, fill: theme.palette.text.secondary }}
                    />
                    <YAxis
                      axisLine={false}
                      tickLine={false}
                      tick={{ fontSize: 12, fill: theme.palette.text.secondary }}
                    />
                    <ChartTooltip
                      cursor={{ fill: 'transparent' }}
                      contentStyle={{
                        borderRadius: 12,
                        border: 'none',
                        boxShadow: theme.shadows[3],
                        backgroundColor: theme.palette.background.paper,
                      }}
                    />
                    {newRegsLast7Keys.map((k, i) => (
                      <Bar key={`nr7-${k}`} dataKey={k} stackId="a" fill={COLORS[i % COLORS.length]} radius={[4, 4, 0, 0]} />
                    ))}
                  </BarChart>
                </ResponsiveContainer>
              </Box>
            </Paper>
          </Box>
        </Box>

        <Box
          sx={{
            display: 'grid',
            gridTemplateColumns: { xs: '1fr', md: 'repeat(2, minmax(0, 1fr))' },
            gap: 1.5,
          }}
        >
          <Box>
            <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
              <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1.25 }}>
                <Box>
                  <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
                    Weekly Activity
                  </Typography>
                  <Typography variant="body2" color="text.secondary">
                    Sessions and usage trend across the week.
                  </Typography>
                </Box>
                <IconButton size="small" aria-label="more">
                  <MoreVertIcon fontSize="small" />
                </IconButton>
              </Stack>
              <Divider sx={{ mb: 1.5 }} />
              <Box sx={{ height: 320 }}>
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={safeAnalytics.activity}>
                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke={theme.palette.divider} />
                    <XAxis
                      dataKey="name"
                      axisLine={false}
                      tickLine={false}
                      tick={{ fontSize: 12, fill: theme.palette.text.secondary }}
                    />
                    <YAxis
                      axisLine={false}
                      tickLine={false}
                      tick={{ fontSize: 12, fill: theme.palette.text.secondary }}
                    />
                    <ChartTooltip
                      cursor={{ fill: 'transparent' }}
                      contentStyle={{
                        borderRadius: 12,
                        border: 'none',
                        boxShadow: theme.shadows[3],
                        backgroundColor: theme.palette.background.paper,
                      }}
                    />
                    <Bar dataKey="value" fill={theme.palette.primary.main} radius={[4, 4, 0, 0]} barSize={28} />
                  </BarChart>
                </ResponsiveContainer>
              </Box>
            </Paper>
          </Box>

          <Box>
            <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
              <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1.25 }}>
                <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
                  Gender Split
                </Typography>
                <Stack direction="row" spacing={1} alignItems="center">
                  <PeopleIcon sx={{ fontSize: 18, color: 'text.secondary' }} />
                </Stack>
              </Stack>
              <Divider sx={{ mb: 1.5 }} />
              <Box sx={{ height: 320 }}>
                <ResponsiveContainer width="100%" height="100%">
                  <PieChart>
                    <Pie
                      data={safeAnalytics.gender}
                      cx="50%"
                      cy="50%"
                      innerRadius={58}
                      outerRadius={80}
                      paddingAngle={4}
                      dataKey="value"
                    >
                      {safeAnalytics.gender.map((entry, index) => (
                        <Cell key={`gender-${entry.name}`} fill={COLORS[index % COLORS.length]} />
                      ))}
                    </Pie>
                    <ChartTooltip
                      contentStyle={{
                        borderRadius: 12,
                        border: 'none',
                        boxShadow: theme.shadows[3],
                        backgroundColor: theme.palette.background.paper,
                      }}
                    />
                    <Legend verticalAlign="bottom" height={36} />
                  </PieChart>
                </ResponsiveContainer>
              </Box>
            </Paper>
          </Box>

          <Box>
            <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
              <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1.25 }}>
                <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
                  Top Regions
                </Typography>
                <IconButton size="small" aria-label="more">
                  <MoreVertIcon fontSize="small" />
                </IconButton>
              </Stack>
              <Divider sx={{ mb: 1.5 }} />
              <Box sx={{ height: 300 }}>
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={safeAnalytics.regions}>
                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke={theme.palette.divider} />
                    <XAxis
                      dataKey="name"
                      axisLine={false}
                      tickLine={false}
                      tick={{ fontSize: 12, fill: theme.palette.text.secondary }}
                    />
                    <YAxis
                      axisLine={false}
                      tickLine={false}
                      tick={{ fontSize: 12, fill: theme.palette.text.secondary }}
                    />
                    <ChartTooltip
                      cursor={{ fill: 'transparent' }}
                      contentStyle={{
                        borderRadius: 12,
                        border: 'none',
                        boxShadow: theme.shadows[3],
                        backgroundColor: theme.palette.background.paper,
                      }}
                    />
                    <Bar dataKey="value" fill={theme.palette.primary.main} radius={[4, 4, 0, 0]} barSize={28} />
                  </BarChart>
                </ResponsiveContainer>
              </Box>
              <Stack
                direction="row"
                spacing={1}
                useFlexGap
                flexWrap="wrap"
                sx={{ mt: 1.5 }}
              >
                {safeAnalytics.app_users.map((item) => (
                  <Chip
                    key={`app-legend-${item.scope || item.name}`}
                    size="small"
                    label={`${item.name}: ${item.value}`}
                    variant="outlined"
                  />
                ))}
              </Stack>
            </Paper>
          </Box>

          <Box>
            <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
              <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1.25 }}>
                <Stack direction="row" spacing={1} alignItems="center">
                  <VerifiedIcon sx={{ fontSize: 18, color: 'text.secondary' }} />
                  <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
                    Verification Status
                  </Typography>
                </Stack>
                <IconButton size="small" aria-label="more">
                  <MoreVertIcon fontSize="small" />
                </IconButton>
              </Stack>
              <Divider sx={{ mb: 1.5 }} />
              <Box sx={{ height: 300 }}>
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart layout="vertical" data={safeAnalytics.verification}>
                    <CartesianGrid strokeDasharray="3 3" horizontal={false} stroke={theme.palette.divider} />
                    <XAxis type="number" hide />
                    <YAxis
                      dataKey="name"
                      type="category"
                      axisLine={false}
                      tickLine={false}
                      tick={{ fontSize: 12, fill: theme.palette.text.secondary }}
                    />
                    <ChartTooltip
                      cursor={{ fill: 'transparent' }}
                      contentStyle={{
                        borderRadius: 12,
                        border: 'none',
                        boxShadow: theme.shadows[3],
                        backgroundColor: theme.palette.background.paper,
                      }}
                    />
                    <Bar dataKey="value" fill={theme.palette.info.main} radius={[0, 4, 4, 0]} barSize={18} />
                  </BarChart>
                </ResponsiveContainer>
              </Box>
            </Paper>
          </Box>
        </Box>

        <Box
          sx={{
            display: 'grid',
            gridTemplateColumns: { xs: '1fr', md: 'repeat(2, minmax(0, 1fr))' },
            gap: 1.5,
          }}
        >
          <Box>
            <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
              <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1.25 }}>
                <Stack direction="row" spacing={1} alignItems="center">
                  <AppsIcon sx={{ fontSize: 18, color: 'text.secondary' }} />
                  <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
                    Users by Application
                  </Typography>
                </Stack>
              </Stack>
              <Divider sx={{ mb: 1.5 }} />
              <Box sx={{ height: 300 }}>
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={safeAnalytics.app_users}>
                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke={theme.palette.divider} />
                    <XAxis
                      dataKey="name"
                      axisLine={false}
                      tickLine={false}
                      tick={{ fontSize: 12, fill: theme.palette.text.secondary }}
                    />
                    <YAxis
                      axisLine={false}
                      tickLine={false}
                      tick={{ fontSize: 12, fill: theme.palette.text.secondary }}
                    />
                    <ChartTooltip
                      cursor={{ fill: 'transparent' }}
                      contentStyle={{
                        borderRadius: 12,
                        border: 'none',
                        boxShadow: theme.shadows[3],
                        backgroundColor: theme.palette.background.paper,
                      }}
                    />
                    <Bar dataKey="value" fill={theme.palette.secondary.main} radius={[4, 4, 0, 0]} barSize={28} />
                  </BarChart>
                </ResponsiveContainer>
              </Box>
            </Paper>
          </Box>

          <Box>
            <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
              <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1.25 }}>
                <Stack direction="row" spacing={1} alignItems="center">
                  <TimelineIcon sx={{ fontSize: 18, color: 'text.secondary' }} />
                  <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
                    Weekly Activity by Application Filter
                  </Typography>
                </Stack>
                <TextField
                  select
                  size="small"
                  label="Language"
                  value={activityLanguageId}
                  onChange={handleActivityLanguageChange}
                  sx={{ minWidth: 180 }}
                >
                  <MenuItem value="all">All Languages</MenuItem>
                  {(activityLanguageOptions || []).map((language) => (
                    <MenuItem key={`activity-language-${language.id}`} value={String(language.id)}>
                      {language.display_name || language.name}
                    </MenuItem>
                  ))}
                </TextField>
              </Stack>
              <Divider sx={{ mb: 1.5 }} />
              <Box sx={{ height: 300 }}>
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={safeAnalytics.app_activity}>
                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke={theme.palette.divider} />
                    <XAxis
                      dataKey="name"
                      axisLine={false}
                      tickLine={false}
                      tick={{ fontSize: 12, fill: theme.palette.text.secondary }}
                    />
                    <YAxis
                      axisLine={false}
                      tickLine={false}
                      tick={{ fontSize: 12, fill: theme.palette.text.secondary }}
                    />
                    <ChartTooltip
                      contentStyle={{
                        borderRadius: 12,
                        border: 'none',
                        boxShadow: theme.shadows[3],
                        backgroundColor: theme.palette.background.paper,
                      }}
                    />
                    <Bar dataKey="value" fill={theme.palette.success.main} radius={[4, 4, 0, 0]} barSize={28} />
                  </BarChart>
                </ResponsiveContainer>
              </Box>
            </Paper>
          </Box>
        </Box>
      </Stack>
    </Box>
  );
}

UserAnalysis.layout = (page) => <AdminLayout children={page} />;
