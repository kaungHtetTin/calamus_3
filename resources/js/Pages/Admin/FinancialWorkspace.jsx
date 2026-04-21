import React, { useMemo } from 'react';
import { Head, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import {
  Avatar,
  Box,
  Card,
  CardContent,
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
  Stack,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TablePagination,
  TableRow,
  TextField,
  Typography,
  useMediaQuery,
  useTheme,
} from '@mui/material';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell,
  Legend,
} from 'recharts';
import {
  Dashboard as DashboardIcon,
  Paid as PaidIcon,
  CreditCard as CreditCardIcon,
  Language as LanguageIcon,
  Payments as PaymentsIcon,
} from '@mui/icons-material';

const tabItems = [
  { key: 'overview', label: 'Overview', icon: <DashboardIcon fontSize="small" /> },
  { key: 'cost', label: 'Cost', icon: <PaidIcon fontSize="small" /> },
  { key: 'payment', label: 'Payment', icon: <CreditCardIcon fontSize="small" /> },
];

export default function FinancialWorkspace({
  languages = [],
  selectedMajor = '',
  selectedLanguage = null,
  tab = 'overview',
  overview = null,
  costsThisMonth = [],
  filter = null,
  salesMonthSeries = [],
  salesYearSeries = [],
  costFilter = null,
  costCategoryYearSeries = [],
  costCategoryAllTimeSeries = [],
  costsPaginator = null,
  paymentFilter = null,
  paymentsPaginator = null,
}) {
  const { admin_app_url } = usePage().props;
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('md'));
  const appBaseUrl = admin_app_url.replace(/\/admin\/?$/, '');

  const major = selectedMajor || (languages[0]?.code ?? '');
  const activeTab = tabItems.some((item) => item.key === tab) ? tab : 'overview';
  const activeTabMeta = useMemo(() => tabItems.find((item) => item.key === activeTab), [activeTab]);
  const selectedYear = Number(filter?.year || overview?.selected_period?.year || new Date().getFullYear());
  const selectedMonth = Number(filter?.month || overview?.selected_period?.month || new Date().getMonth() + 1);
  const yearOptions = Array.isArray(filter?.years) && filter.years.length > 0 ? filter.years : [selectedYear];
  const costSelectedYear = Number(costFilter?.year || new Date().getFullYear());
  const costSelectedMonth = Number(costFilter?.month ?? 0);
  const costYearOptions = Array.isArray(costFilter?.years) && costFilter.years.length > 0 ? costFilter.years : [costSelectedYear];

  const monthOptions = useMemo(() => {
    return [
      { value: 1, label: 'Jan' },
      { value: 2, label: 'Feb' },
      { value: 3, label: 'Mar' },
      { value: 4, label: 'Apr' },
      { value: 5, label: 'May' },
      { value: 6, label: 'Jun' },
      { value: 7, label: 'Jul' },
      { value: 8, label: 'Aug' },
      { value: 9, label: 'Sep' },
      { value: 10, label: 'Oct' },
      { value: 11, label: 'Nov' },
      { value: 12, label: 'Dec' },
    ];
  }, []);

  const selectedMonthLabel = useMemo(() => {
    return monthOptions.find((m) => m.value === selectedMonth)?.label || String(selectedMonth);
  }, [monthOptions, selectedMonth]);

  const costMonthLabel = useMemo(() => {
    if (!costSelectedMonth) {
      return 'All';
    }
    return monthOptions.find((m) => m.value === costSelectedMonth)?.label || String(costSelectedMonth);
  }, [costSelectedMonth, monthOptions]);

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

  const openWorkspace = (nextMajor, nextTab, options = {}) => {
    const params = new URLSearchParams();
    params.set('major', nextMajor);
    if (nextTab) {
      params.set('tab', nextTab);
    }

    const tabKey = nextTab || activeTab;
    if (tabKey === 'overview') {
      params.set('year', String(options.year ?? selectedYear));
      params.set('month', String(options.month ?? selectedMonth));
    }
    if (tabKey === 'cost') {
      params.set('year', String(options.year ?? costSelectedYear));
      params.set('month', String(options.month ?? costSelectedMonth));
      if (options.cost_page) {
        params.set('cost_page', String(options.cost_page));
      }
      if (options.cost_per_page) {
        params.set('cost_per_page', String(options.cost_per_page));
      }
    }
    if (tabKey === 'payment') {
      params.set('year', String(options.year ?? paymentSelectedYear));
      params.set('month', String(options.month ?? paymentSelectedMonth));
      if (options.search !== undefined) {
        const keyword = String(options.search || '').trim();
        if (keyword) {
          params.set('search', keyword);
        } else {
          params.delete('search');
        }
      } else if (paymentSearch.trim()) {
        params.set('search', paymentSearch.trim());
      }
      if (options.payment_page) {
        params.set('payment_page', String(options.payment_page));
      }
      if (options.payment_per_page) {
        params.set('payment_per_page', String(options.payment_per_page));
      }
    }

    window.location.href = `${admin_app_url}/financial/workspace?${params.toString()}`;
  };

  const formatCurrency = (value) => {
    const n = Number(value || 0);
    return new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(n);
  };

  const normalizePieSeries = (items, limit = 8) => {
    const rows = Array.isArray(items) ? items : [];
    const sorted = [...rows].sort((a, b) => Number(b.value || 0) - Number(a.value || 0));
    const top = sorted.slice(0, limit);
    const rest = sorted.slice(limit);
    const otherTotal = rest.reduce((sum, r) => sum + Number(r.value || 0), 0);
    if (otherTotal > 0) {
      top.push({ name: 'Other', value: otherTotal });
    }
    return top;
  };

  const yearCostPieData = useMemo(() => normalizePieSeries(costCategoryYearSeries), [costCategoryYearSeries]);
  const allTimeCostPieData = useMemo(() => normalizePieSeries(costCategoryAllTimeSeries), [costCategoryAllTimeSeries]);
  const pieColors = ['#1976d2', '#2e7d32', '#ed6c02', '#9c27b0', '#d32f2f', '#0288d1', '#6d4c41', '#455a64', '#7cb342'];

  const costRows = useMemo(() => {
    if (costsPaginator && Array.isArray(costsPaginator.data)) {
      return costsPaginator.data;
    }
    return [];
  }, [costsPaginator]);
  const costTotal = Number(costsPaginator?.total ?? costRows.length);
  const costPerPage = Number(costsPaginator?.per_page ?? 25);
  const costPage = Number(costsPaginator?.current_page ?? 1);

  const paymentSelectedYear = Number(paymentFilter?.year || new Date().getFullYear());
  const paymentSelectedMonth = Number(paymentFilter?.month ?? 0);
  const paymentYearOptions = Array.isArray(paymentFilter?.years) && paymentFilter.years.length > 0 ? paymentFilter.years : [paymentSelectedYear];
  const paymentSearch = String(paymentFilter?.search || '');
  const paymentMonthLabel = useMemo(() => {
    if (!paymentSelectedMonth) {
      return 'All';
    }
    return monthOptions.find((m) => m.value === paymentSelectedMonth)?.label || String(paymentSelectedMonth);
  }, [monthOptions, paymentSelectedMonth]);

  const paymentRows = useMemo(() => {
    if (paymentsPaginator && Array.isArray(paymentsPaginator.data)) {
      return paymentsPaginator.data;
    }
    return [];
  }, [paymentsPaginator]);
  const paymentTotal = Number(paymentsPaginator?.total ?? paymentRows.length);
  const paymentPerPage = Number(paymentsPaginator?.per_page ?? 25);
  const paymentPage = Number(paymentsPaginator?.current_page ?? 1);

  return (
    <Box>
      <Head title="Financial Workspace" />
      <Stack spacing={2.5}>
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              Financial Workspace
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Channel: {major || '-'}
            </Typography>
          </Box>
        </Box>

        <Box sx={{ display: { xs: 'block', md: 'flex' }, gap: 2, alignItems: 'flex-start' }}>
          <Stack spacing={1.5} sx={{ flex: 1, minWidth: 0 }}>
            {activeTab === 'overview' ? (
              <Stack spacing={1.5}>
                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                  <Stack direction="row" spacing={1} alignItems="center" sx={{ mb: 0.75 }}>
                    {activeTabMeta?.icon}
                    <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                      Overview
                    </Typography>
                    <Chip size="small" label={major || '-'} variant="outlined" />
                    {overview?.as_of ? (
                      <Chip size="small" label={`As of: ${overview.as_of}`} variant="outlined" />
                    ) : null}
                  </Stack>
                  <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', sm: '220px 220px 1fr' }, gap: 1, alignItems: 'center' }}>
                    <TextField
                      size="small"
                      select
                      fullWidth
                      label="Year"
                      value={selectedYear}
                      onChange={(event) => openWorkspace(major, 'overview', { year: Number(event.target.value), month: selectedMonth })}
                    >
                      {yearOptions.map((y) => (
                        <MenuItem key={`year-${y}`} value={y}>
                          {y}
                        </MenuItem>
                      ))}
                    </TextField>
                    <TextField
                      size="small"
                      select
                      fullWidth
                      label="Month"
                      value={selectedMonth}
                      onChange={(event) => openWorkspace(major, 'overview', { year: selectedYear, month: Number(event.target.value) })}
                    >
                      {monthOptions.map((m) => (
                        <MenuItem key={`month-${m.value}`} value={m.value}>
                          {m.label}
                        </MenuItem>
                      ))}
                    </TextField>
                    <Typography
                      variant="body2"
                      color="text.secondary"
                      sx={{ display: { xs: 'none', sm: 'block' }, gridColumn: '1 / -1', mt: 0.5 }}
                    >
                      Filtering affects Month / Year metrics and monthly cost list.
                    </Typography>
                  </Box>
                </Paper>

                <Grid container spacing={1.25}>
                  <Grid item xs={12} md={6} lg={3}>
                    <Card variant="outlined" sx={{ borderRadius: 2 }}>
                      <CardContent sx={{ pb: 2 }}>
                        <Typography variant="caption" color="text.secondary">
                          Income Today
                        </Typography>
                        <Typography variant="h6" sx={{ fontWeight: 800, mt: 0.25 }}>
                          {formatCurrency(overview?.income?.today)}
                        </Typography>
                      </CardContent>
                    </Card>
                  </Grid>
                  <Grid item xs={12} md={6} lg={3}>
                    <Card variant="outlined" sx={{ borderRadius: 2 }}>
                      <CardContent sx={{ pb: 2 }}>
                        <Typography variant="caption" color="text.secondary">
                          Income ({selectedMonthLabel} {selectedYear})
                        </Typography>
                        <Typography variant="h6" sx={{ fontWeight: 800, mt: 0.25 }}>
                          {formatCurrency(overview?.income?.month)}
                        </Typography>
                      </CardContent>
                    </Card>
                  </Grid>
                  <Grid item xs={12} md={6} lg={3}>
                    <Card variant="outlined" sx={{ borderRadius: 2 }}>
                      <CardContent sx={{ pb: 2 }}>
                        <Typography variant="caption" color="text.secondary">
                          Income ({selectedYear})
                        </Typography>
                        <Typography variant="h6" sx={{ fontWeight: 800, mt: 0.25 }}>
                          {formatCurrency(overview?.income?.year)}
                        </Typography>
                      </CardContent>
                    </Card>
                  </Grid>
                  <Grid item xs={12} md={6} lg={3}>
                    <Card variant="outlined" sx={{ borderRadius: 2 }}>
                      <CardContent sx={{ pb: 2 }}>
                        <Typography variant="caption" color="text.secondary">
                          Income All Time
                        </Typography>
                        <Typography variant="h6" sx={{ fontWeight: 800, mt: 0.25 }}>
                          {formatCurrency(overview?.income?.all_time)}
                        </Typography>
                      </CardContent>
                    </Card>
                  </Grid>
                </Grid>

                <Grid container spacing={1.25}>
                  <Grid item xs={12} md={4}>
                    <Card
                      variant="outlined"
                      sx={{
                        borderRadius: 2,
                        borderColor: (overview?.net_income?.month ?? 0) < 0 ? 'error.main' : 'divider',
                      }}
                    >
                      <CardContent sx={{ pb: 2 }}>
                        <Typography variant="caption" color="text.secondary">
                          Net Income ({selectedMonthLabel} {selectedYear})
                        </Typography>
                        <Typography
                          variant="h6"
                          sx={{ fontWeight: 800, mt: 0.25, color: (overview?.net_income?.month ?? 0) < 0 ? 'error.main' : 'text.primary' }}
                        >
                          {formatCurrency(overview?.net_income?.month)}
                        </Typography>
                        <Typography variant="caption" color="text.secondary">
                          Income {formatCurrency(overview?.income?.month)} − Cost {formatCurrency(overview?.cost?.month)}
                        </Typography>
                      </CardContent>
                    </Card>
                  </Grid>
                  <Grid item xs={12} md={4}>
                    <Card
                      variant="outlined"
                      sx={{
                        borderRadius: 2,
                        borderColor: (overview?.net_income?.year ?? 0) < 0 ? 'error.main' : 'divider',
                      }}
                    >
                      <CardContent sx={{ pb: 2 }}>
                        <Typography variant="caption" color="text.secondary">
                          Net Income ({selectedYear})
                        </Typography>
                        <Typography
                          variant="h6"
                          sx={{ fontWeight: 800, mt: 0.25, color: (overview?.net_income?.year ?? 0) < 0 ? 'error.main' : 'text.primary' }}
                        >
                          {formatCurrency(overview?.net_income?.year)}
                        </Typography>
                        <Typography variant="caption" color="text.secondary">
                          Income {formatCurrency(overview?.income?.year)} − Cost {formatCurrency(overview?.cost?.year)}
                        </Typography>
                      </CardContent>
                    </Card>
                  </Grid>
                  <Grid item xs={12} md={4}>
                    <Card
                      variant="outlined"
                      sx={{
                        borderRadius: 2,
                        borderColor: (overview?.net_income?.all_time ?? 0) < 0 ? 'error.main' : 'divider',
                      }}
                    >
                      <CardContent sx={{ pb: 2 }}>
                        <Typography variant="caption" color="text.secondary">
                          Net Income All Time
                        </Typography>
                        <Typography
                          variant="h6"
                          sx={{ fontWeight: 800, mt: 0.25, color: (overview?.net_income?.all_time ?? 0) < 0 ? 'error.main' : 'text.primary' }}
                        >
                          {formatCurrency(overview?.net_income?.all_time)}
                        </Typography>
                        <Typography variant="caption" color="text.secondary">
                          Income {formatCurrency(overview?.income?.all_time)} − Cost {formatCurrency(overview?.cost?.all_time)}
                        </Typography>
                      </CardContent>
                    </Card>
                  </Grid>
                </Grid>

                <Stack spacing={1.25}>
                  <Card variant="outlined" sx={{ borderRadius: 2 }}>
                    <CardContent sx={{ pb: 1 }}>
                      <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1 }}>
                        Sales by Day ({selectedMonthLabel} {selectedYear})
                      </Typography>
                      <Box sx={{ width: '100%', height: 260 }}>
                        <ResponsiveContainer width="100%" height="100%">
                          <BarChart data={salesMonthSeries}>
                            <CartesianGrid strokeDasharray="3 3" />
                            <XAxis dataKey="x" tick={{ fontSize: 11 }} />
                            <YAxis tick={{ fontSize: 11 }} />
                            <Tooltip />
                            <Bar dataKey="y" name="Amount" fill="#1976d2" radius={[3, 3, 0, 0]} />
                          </BarChart>
                        </ResponsiveContainer>
                      </Box>
                    </CardContent>
                  </Card>
                  <Card variant="outlined" sx={{ borderRadius: 2 }}>
                    <CardContent sx={{ pb: 1 }}>
                      <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1 }}>
                        Sales by Month ({selectedYear})
                      </Typography>
                      <Box sx={{ width: '100%', height: 260 }}>
                        <ResponsiveContainer width="100%" height="100%">
                          <BarChart data={salesYearSeries}>
                            <CartesianGrid strokeDasharray="3 3" />
                            <XAxis dataKey="x" tick={{ fontSize: 11 }} />
                            <YAxis tick={{ fontSize: 11 }} />
                            <Tooltip />
                            <Bar dataKey="y" name="Amount" fill="#2e7d32" radius={[3, 3, 0, 0]} />
                          </BarChart>
                        </ResponsiveContainer>
                      </Box>
                    </CardContent>
                  </Card>
                </Stack>

                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                  <Stack direction="row" spacing={1} alignItems="center" justifyContent="space-between" sx={{ mb: 1 }}>
                    <Stack direction="row" spacing={1} alignItems="center">
                      <PaidIcon fontSize="small" />
                      <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                        Cost List ({selectedMonthLabel} {selectedYear})
                      </Typography>
                      <Chip size="small" label={`Total: ${formatCurrency(overview?.cost?.month)}`} variant="outlined" />
                    </Stack>
                  </Stack>

                  <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
                    <Table size="small" sx={{ tableLayout: 'fixed', '& .MuiTableCell-root': { px: 1, py: 0.75 } }}>
                      <TableHead>
                        <TableRow>
                          <TableCell sx={{ fontWeight: 700 }}>Title</TableCell>
                          <TableCell sx={{ fontWeight: 700, width: 160 }}>Category</TableCell>
                          <TableCell sx={{ fontWeight: 700, width: 140 }}>Amount</TableCell>
                          <TableCell sx={{ fontWeight: 700, width: 160 }}>Date</TableCell>
                        </TableRow>
                      </TableHead>
                      <TableBody>
                        {(costsThisMonth || []).map((row) => (
                          <TableRow key={`cost-${row.id}`} hover>
                            <TableCell>
                              <Typography variant="body2" sx={{ fontWeight: 600 }} noWrap title={row.title || ''}>
                                {row.title}
                              </Typography>
                              {row.transfer_id ? (
                                <Typography
                                  variant="caption"
                                  color="text.secondary"
                                  noWrap
                                  title={String(row.transfer_id || '')}
                                  sx={{ display: 'block' }}
                                >
                                  Transfer: {row.transfer_id}
                                </Typography>
                              ) : null}
                            </TableCell>
                            <TableCell>
                              <Typography variant="body2" color="text.secondary" noWrap title={row.category_title || ''}>
                                {row.category_title || '-'}
                              </Typography>
                            </TableCell>
                            <TableCell>
                              <Typography variant="body2" sx={{ fontWeight: 700 }}>
                                {formatCurrency(row.amount)}
                              </Typography>
                            </TableCell>
                            <TableCell>
                              <Typography variant="body2" color="text.secondary" noWrap>
                                {String(row.date || '').replace('T', ' ').slice(0, 19)}
                              </Typography>
                            </TableCell>
                          </TableRow>
                        ))}
                        {(costsThisMonth || []).length === 0 && (
                          <TableRow>
                            <TableCell colSpan={4}>
                              <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                                No costs recorded for this month.
                              </Typography>
                            </TableCell>
                          </TableRow>
                        )}
                      </TableBody>
                    </Table>
                  </TableContainer>
                </Paper>
              </Stack>
            ) : activeTab === 'cost' ? (
              <Stack spacing={1.5}>
                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                  <Stack direction="row" spacing={1} alignItems="center" sx={{ mb: 0.75 }}>
                    {activeTabMeta?.icon}
                    <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                      Cost
                    </Typography>
                    <Chip size="small" label={major || '-'} variant="outlined" />
                  </Stack>

                  <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', sm: '220px 220px 1fr' }, gap: 1, alignItems: 'center' }}>
                    <TextField
                      size="small"
                      select
                      fullWidth
                      label="Year"
                      value={costSelectedYear}
                      onChange={(event) =>
                        openWorkspace(major, 'cost', {
                          year: Number(event.target.value),
                          month: costSelectedMonth,
                          cost_page: 1,
                          cost_per_page: costPerPage,
                        })
                      }
                    >
                      {costYearOptions.map((y) => (
                        <MenuItem key={`cost-year-${y}`} value={y}>
                          {y}
                        </MenuItem>
                      ))}
                    </TextField>
                    <TextField
                      size="small"
                      select
                      fullWidth
                      label="Month"
                      value={costSelectedMonth}
                      onChange={(event) =>
                        openWorkspace(major, 'cost', {
                          year: costSelectedYear,
                          month: Number(event.target.value),
                          cost_page: 1,
                          cost_per_page: costPerPage,
                        })
                      }
                    >
                      <MenuItem value={0}>All</MenuItem>
                      {monthOptions.map((m) => (
                        <MenuItem key={`cost-month-${m.value}`} value={m.value}>
                          {m.label}
                        </MenuItem>
                      ))}
                    </TextField>
                    <Typography
                      variant="body2"
                      color="text.secondary"
                      sx={{ display: { xs: 'none', sm: 'block' }, gridColumn: '1 / -1', mt: 0.5 }}
                    >
                      Filtering affects the cost list. Charts show breakdown for the selected year and all time.
                    </Typography>
                  </Box>
                </Paper>

                <Box sx={{ display: { xs: 'block', md: 'flex' }, gap: 1.25 }}>
                  <Card variant="outlined" sx={{ borderRadius: 2, flex: 1, minWidth: 0 }}>
                    <CardContent sx={{ pb: 1 }}>
                      <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1 }}>
                        Cost by Category ({costSelectedYear})
                      </Typography>
                      <Box sx={{ width: '100%', height: 320 }}>
                        <ResponsiveContainer width="100%" height="100%">
                          <PieChart>
                            <Tooltip />
                            <Legend />
                            <Pie data={yearCostPieData} dataKey="value" nameKey="name" outerRadius={98} innerRadius={65} paddingAngle={2}>
                              {yearCostPieData.map((_, idx) => (
                                <Cell key={`year-cell-${idx}`} fill={pieColors[idx % pieColors.length]} />
                              ))}
                            </Pie>
                          </PieChart>
                        </ResponsiveContainer>
                      </Box>
                    </CardContent>
                  </Card>

                  <Card variant="outlined" sx={{ borderRadius: 2, flex: 1, minWidth: 0, mt: { xs: 1.25, md: 0 } }}>
                    <CardContent sx={{ pb: 1 }}>
                      <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1 }}>
                        Cost by Category (All Time)
                      </Typography>
                      <Box sx={{ width: '100%', height: 320 }}>
                        <ResponsiveContainer width="100%" height="100%">
                          <PieChart>
                            <Tooltip />
                            <Legend />
                            <Pie data={allTimeCostPieData} dataKey="value" nameKey="name" outerRadius={98} innerRadius={65} paddingAngle={2}>
                              {allTimeCostPieData.map((_, idx) => (
                                <Cell key={`alltime-cell-${idx}`} fill={pieColors[idx % pieColors.length]} />
                              ))}
                            </Pie>
                          </PieChart>
                        </ResponsiveContainer>
                      </Box>
                    </CardContent>
                  </Card>
                </Box>

                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                  <Stack direction="row" spacing={1} alignItems="center" justifyContent="space-between" sx={{ mb: 1 }}>
                    <Stack direction="row" spacing={1} alignItems="center">
                      <PaidIcon fontSize="small" />
                      <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                        Cost List ({costMonthLabel} {costSelectedYear})
                      </Typography>
                      <Chip size="small" label={`Records: ${costTotal}`} variant="outlined" />
                    </Stack>
                  </Stack>

                  <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
                    <Table size="small" sx={{ tableLayout: 'auto', '& .MuiTableCell-root': { px: 1, py: 0.75, verticalAlign: 'top' } }}>
                      <TableHead>
                        <TableRow>
                          <TableCell sx={{ fontWeight: 700, minWidth: 280 }}>Title</TableCell>
                          <TableCell sx={{ fontWeight: 700, width: 170 }}>Category</TableCell>
                          <TableCell sx={{ fontWeight: 700, width: 120 }}>Amount</TableCell>
                          <TableCell sx={{ fontWeight: 700, width: 140 }}>Date</TableCell>
                        </TableRow>
                      </TableHead>
                      <TableBody>
                        {costRows.map((row) => (
                          <TableRow key={`cost-tab-${row.id}`} hover>
                            <TableCell>
                              <Typography variant="body2" sx={{ fontWeight: 600, whiteSpace: 'normal', wordBreak: 'break-word' }} title={row.title || ''}>
                                {row.title}
                              </Typography>
                              {row.transfer_id ? (
                                <Typography
                                  variant="caption"
                                  color="text.secondary"
                                  title={String(row.transfer_id || '')}
                                  sx={{ display: 'block' }}
                                >
                                  Transfer: {row.transfer_id}
                                </Typography>
                              ) : null}
                            </TableCell>
                            <TableCell>
                              <Typography
                                variant="body2"
                                color="text.secondary"
                                sx={{ whiteSpace: 'normal', wordBreak: 'break-word' }}
                                title={row.category_title || ''}
                              >
                                {row.category_title || '-'}
                              </Typography>
                            </TableCell>
                            <TableCell>
                              <Typography variant="body2" sx={{ fontWeight: 700 }}>
                                {formatCurrency(row.amount)}
                              </Typography>
                            </TableCell>
                            <TableCell>
                              <Typography variant="body2" color="text.secondary" sx={{ whiteSpace: 'normal' }}>
                                {String(row.date || '').replace('T', ' ').slice(0, 19)}
                              </Typography>
                            </TableCell>
                          </TableRow>
                        ))}
                        {costRows.length === 0 && (
                          <TableRow>
                            <TableCell colSpan={4}>
                              <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                                No costs found.
                              </Typography>
                            </TableCell>
                          </TableRow>
                        )}
                      </TableBody>
                    </Table>
                  </TableContainer>

                  <TablePagination
                    component="div"
                    count={costTotal}
                    page={Math.max(0, costPage - 1)}
                    rowsPerPage={costPerPage}
                    rowsPerPageOptions={[10, 25, 50, 100]}
                    onPageChange={(_, nextPage) => {
                      openWorkspace(major, 'cost', {
                        year: costSelectedYear,
                        month: costSelectedMonth,
                        cost_page: nextPage + 1,
                        cost_per_page: costPerPage,
                      });
                    }}
                    onRowsPerPageChange={(event) => {
                      const nextValue = Number(event.target.value || 25);
                      openWorkspace(major, 'cost', {
                        year: costSelectedYear,
                        month: costSelectedMonth,
                        cost_page: 1,
                        cost_per_page: nextValue,
                      });
                    }}
                  />
                </Paper>
              </Stack>
            ) : activeTab === 'payment' ? (
              <Stack spacing={1.5}>
                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                  <Stack direction="row" spacing={1} alignItems="center" sx={{ mb: 0.75 }}>
                    {activeTabMeta?.icon}
                    <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                      Payments
                    </Typography>
                    <Chip size="small" label={major || '-'} variant="outlined" />
                  </Stack>

                  <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', sm: '220px 220px 1fr' }, gap: 1, alignItems: 'center' }}>
                    <TextField
                      size="small"
                      select
                      fullWidth
                      label="Year"
                      value={paymentSelectedYear}
                      onChange={(event) =>
                        openWorkspace(major, 'payment', {
                          year: Number(event.target.value),
                          month: paymentSelectedMonth,
                          search: paymentSearch,
                          payment_page: 1,
                          payment_per_page: paymentPerPage,
                        })
                      }
                    >
                      {paymentYearOptions.map((y) => (
                        <MenuItem key={`pay-year-${y}`} value={y}>
                          {y}
                        </MenuItem>
                      ))}
                    </TextField>
                    <TextField
                      size="small"
                      select
                      fullWidth
                      label="Month"
                      value={paymentSelectedMonth}
                      onChange={(event) =>
                        openWorkspace(major, 'payment', {
                          year: paymentSelectedYear,
                          month: Number(event.target.value),
                          search: paymentSearch,
                          payment_page: 1,
                          payment_per_page: paymentPerPage,
                        })
                      }
                    >
                      <MenuItem value={0}>All</MenuItem>
                      {monthOptions.map((m) => (
                        <MenuItem key={`pay-month-${m.value}`} value={m.value}>
                          {m.label}
                        </MenuItem>
                      ))}
                    </TextField>
                    <TextField
                      size="small"
                      fullWidth
                      label="Search (user_id / transaction_id / amount)"
                      value={paymentSearch}
                      onChange={(event) =>
                        openWorkspace(major, 'payment', {
                          year: paymentSelectedYear,
                          month: paymentSelectedMonth,
                          search: event.target.value,
                          payment_page: 1,
                          payment_per_page: paymentPerPage,
                        })
                      }
                    />
                    <Typography
                      variant="body2"
                      color="text.secondary"
                      sx={{ display: { xs: 'none', sm: 'block' }, gridColumn: '1 / -1', mt: 0.5 }}
                    >
                      Filters affect the list only. No actions are available in Payments tab.
                    </Typography>
                  </Box>
                </Paper>

                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                  <Stack direction="row" spacing={1} alignItems="center" justifyContent="space-between" sx={{ mb: 1 }}>
                    <Stack direction="row" spacing={1} alignItems="center">
                      <PaymentsIcon fontSize="small" />
                      <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                        Payment List ({paymentMonthLabel} {paymentSelectedYear})
                      </Typography>
                      <Chip size="small" label={`Records: ${paymentTotal}`} variant="outlined" />
                    </Stack>
                  </Stack>

                  <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
                    <Table size="small" sx={{ tableLayout: 'auto', '& .MuiTableCell-root': { px: 1, py: 0.75, verticalAlign: 'top' } }}>
                      <TableHead>
                        <TableRow>
                          <TableCell sx={{ fontWeight: 700, width: 90 }}>ID</TableCell>
                          <TableCell sx={{ fontWeight: 700, width: 120 }}>User</TableCell>
                          <TableCell sx={{ fontWeight: 700, width: 130 }}>Amount</TableCell>
                          <TableCell sx={{ fontWeight: 700, minWidth: 220 }}>Transaction</TableCell>
                          <TableCell sx={{ fontWeight: 700, width: 160 }}>Date</TableCell>
                        </TableRow>
                      </TableHead>
                      <TableBody>
                        {paymentRows.map((row) => (
                          <TableRow key={`pay-${row.id}`} hover>
                            <TableCell>
                              <Typography variant="body2" sx={{ fontWeight: 700 }}>
                                {row.id}
                              </Typography>
                            </TableCell>
                            <TableCell>
                              <Typography variant="body2" sx={{ fontWeight: 600 }}>
                                {row.user_id}
                              </Typography>
                            </TableCell>
                            <TableCell>
                              <Typography variant="body2" sx={{ fontWeight: 700 }}>
                                {formatCurrency(row.amount)}
                              </Typography>
                            </TableCell>
                            <TableCell>
                              <Typography variant="body2" color="text.secondary" sx={{ whiteSpace: 'normal', wordBreak: 'break-word' }}>
                                {row.transaction_id || '-'}
                              </Typography>
                            </TableCell>
                            <TableCell>
                              <Typography variant="body2" color="text.secondary" sx={{ whiteSpace: 'normal' }}>
                                {String(row.date || '').replace('T', ' ').slice(0, 19)}
                              </Typography>
                            </TableCell>
                          </TableRow>
                        ))}
                        {paymentRows.length === 0 && (
                          <TableRow>
                            <TableCell colSpan={5}>
                              <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                                No payments found.
                              </Typography>
                            </TableCell>
                          </TableRow>
                        )}
                      </TableBody>
                    </Table>
                  </TableContainer>

                  <TablePagination
                    component="div"
                    count={paymentTotal}
                    page={Math.max(0, paymentPage - 1)}
                    rowsPerPage={paymentPerPage}
                    rowsPerPageOptions={[10, 25, 50, 100]}
                    onPageChange={(_, nextPage) => {
                      openWorkspace(major, 'payment', {
                        year: paymentSelectedYear,
                        month: paymentSelectedMonth,
                        search: paymentSearch,
                        payment_page: nextPage + 1,
                        payment_per_page: paymentPerPage,
                      });
                    }}
                    onRowsPerPageChange={(event) => {
                      const nextValue = Number(event.target.value || 25);
                      openWorkspace(major, 'payment', {
                        year: paymentSelectedYear,
                        month: paymentSelectedMonth,
                        search: paymentSearch,
                        payment_page: 1,
                        payment_per_page: nextValue,
                      });
                    }}
                  />
                </Paper>
              </Stack>
            ) : (
              <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                <Stack direction="row" spacing={1} alignItems="center" sx={{ mb: 1 }}>
                  {activeTabMeta?.icon}
                  <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                    {activeTabMeta?.label || 'Financial'}
                  </Typography>
                  <Chip size="small" label="Placeholder" variant="outlined" />
                </Stack>
                <Typography variant="body2" color="text.secondary">
                  Placeholder content for {activeTabMeta?.label || activeTab}.
                </Typography>
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
              width: { xs: '100%', md: 260 },
            }}
          >
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
                onChange={(event) => openWorkspace(event.target.value, activeTab)}
              >
                {languages.map((l) => (
                  <MenuItem key={l.code} value={l.code}>
                    {l.display_name || l.name || l.code}
                  </MenuItem>
                ))}
              </TextField>
            </Box>

            <List dense sx={{ p: 1 }}>
              {tabItems.map((item) => (
                <ListItem key={item.key} disablePadding sx={{ mb: 0.25 }}>
                  <ListItemButton selected={item.key === activeTab} sx={{ borderRadius: 1 }} onClick={() => openWorkspace(major, item.key)}>
                    <ListItemIcon sx={{ minWidth: 34 }}>{item.icon}</ListItemIcon>
                    <ListItemText
                      primary={item.label}
                      primaryTypographyProps={{ fontSize: 13, fontWeight: item.key === activeTab ? 700 : 500, noWrap: true }}
                    />
                  </ListItemButton>
                </ListItem>
              ))}
            </List>
            {isMobile && <Divider />}
          </Paper>
        </Box>
      </Stack>
    </Box>
  );
}

FinancialWorkspace.layout = (page) => <AdminLayout children={page} />;
