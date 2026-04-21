import React, { useMemo, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import {
  Box,
  Button,
  Chip,
  Collapse,
  Grid,
  IconButton,
  MenuItem,
  Pagination,
  Paper,
  Stack,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  TextField,
  Tooltip,
  Typography,
} from '@mui/material';
import { Clear as ClearIcon, ExpandMore as ExpandMoreIcon } from '@mui/icons-material';

export default function ActivityLogs({ logs, filters, adminOptions }) {
  const { admin_app_url } = usePage().props;
  const [expandedId, setExpandedId] = useState(null);
  const [localFilters, setLocalFilters] = useState({
    q: filters?.q || '',
    admin_id: filters?.admin_id || '',
    action: filters?.action || '',
    method: filters?.method || '',
    date_from: filters?.date_from || '',
    date_to: filters?.date_to || '',
  });

  const rows = Array.isArray(logs?.data) ? logs.data : [];
  const admins = Array.isArray(adminOptions) ? adminOptions : [];

  const hasActiveFilters = useMemo(() => {
    return Object.values(localFilters).some((value) => String(value || '').trim() !== '');
  }, [localFilters]);

  const load = (nextFilters) => {
    const cleaned = {};
    Object.entries(nextFilters).forEach(([key, value]) => {
      const v = String(value ?? '').trim();
      if (v !== '') {
        cleaned[key] = value;
      }
    });

    router.get(`${admin_app_url}/activity-logs`, cleaned, {
      preserveScroll: true,
      preserveState: true,
    });
  };

  const applyFilters = () => {
    load({ ...localFilters, page: 1 });
  };

  const resetFilters = () => {
    const cleared = {
      q: '',
      admin_id: '',
      action: '',
      method: '',
      date_from: '',
      date_to: '',
    };
    setLocalFilters(cleared);
    router.get(`${admin_app_url}/activity-logs`, {}, {
      preserveScroll: true,
      preserveState: true,
    });
  };

  const onPageChange = (_, page) => {
    load({ ...localFilters, page });
  };

  const clearLogs = () => {
    const total = Number(logs?.total || 0);
    if (!total) return;
    const confirmText = window.prompt('Type CLEAR to delete all activity logs.');
    if (confirmText !== 'CLEAR') return;
    router.post(`${admin_app_url}/activity-logs/clear`, {}, { preserveScroll: true });
  };

  const formatDateTime = (value) => {
    if (!value) return '-';
    const dt = new Date(value);
    if (Number.isNaN(dt.getTime())) return String(value);
    return dt.toLocaleString();
  };

  const methodColor = (method) => {
    const key = String(method || '').toUpperCase();
    if (key === 'POST') return 'success';
    if (key === 'PATCH' || key === 'PUT') return 'warning';
    if (key === 'DELETE') return 'error';
    return 'default';
  };

  const actionColor = (action) => {
    const key = String(action || '').toLowerCase();
    if (key === 'create') return 'success';
    if (key === 'update') return 'warning';
    if (key === 'delete') return 'error';
    return 'default';
  };

  return (
    <Box>
      <Head title="Activity Logs" />

      <Stack spacing={2}>
        <Stack direction="row" justifyContent="space-between" alignItems="center" spacing={2}>
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              Activity Logs
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Audit trail of admin create, update, and delete actions.
            </Typography>
          </Box>
          <Button
            variant="outlined"
            color="error"
            size="small"
            onClick={clearLogs}
            disabled={Number(logs?.total || 0) === 0}
          >
            Clear Logs
          </Button>
        </Stack>

        <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
          <Grid container spacing={1.25}>
            <Grid item xs={12} md={4}>
              <TextField
                label="Search"
                size="small"
                fullWidth
                value={localFilters.q}
                onChange={(e) => setLocalFilters((prev) => ({ ...prev, q: e.target.value }))}
                placeholder="Admin, path, route"
              />
            </Grid>
            <Grid item xs={12} sm={6} md={2}>
              <TextField
                select
                label="Admin"
                size="small"
                fullWidth
                sx={{ minWidth: 220 }}
                value={localFilters.admin_id}
                onChange={(e) => setLocalFilters((prev) => ({ ...prev, admin_id: e.target.value }))}
              >
                <MenuItem value="">All</MenuItem>
                {admins.map((admin) => (
                  <MenuItem key={`admin-${admin.id}`} value={String(admin.id)}>
                    {admin.name || admin.email || `Admin ${admin.id}`}
                  </MenuItem>
                ))}
              </TextField>
            </Grid>
            <Grid item xs={12} sm={6} md={2}>
              <TextField
                select
                label="Action"
                size="small"
                fullWidth
                sx={{ minWidth: 160 }}
                value={localFilters.action}
                onChange={(e) => setLocalFilters((prev) => ({ ...prev, action: e.target.value }))}
              >
                <MenuItem value="">All</MenuItem>
                <MenuItem value="create">Create</MenuItem>
                <MenuItem value="update">Update</MenuItem>
                <MenuItem value="delete">Delete</MenuItem>
              </TextField>
            </Grid>
            <Grid item xs={12} sm={6} md={2}>
              <TextField
                select
                label="Method"
                size="small"
                fullWidth
                sx={{ minWidth: 160 }}
                value={localFilters.method}
                onChange={(e) => setLocalFilters((prev) => ({ ...prev, method: e.target.value }))}
              >
                <MenuItem value="">All</MenuItem>
                <MenuItem value="POST">POST</MenuItem>
                <MenuItem value="PUT">PUT</MenuItem>
                <MenuItem value="PATCH">PATCH</MenuItem>
                <MenuItem value="DELETE">DELETE</MenuItem>
              </TextField>
            </Grid>
            <Grid item xs={12} sm={6} md={2}>
              <TextField
                type="date"
                label="From"
                size="small"
                fullWidth
                sx={{ minWidth: 160 }}
                value={localFilters.date_from}
                onChange={(e) => setLocalFilters((prev) => ({ ...prev, date_from: e.target.value }))}
                InputLabelProps={{ shrink: true }}
              />
            </Grid>
            <Grid item xs={12} sm={6} md={2}>
              <TextField
                type="date"
                label="To"
                size="small"
                fullWidth
                sx={{ minWidth: 160 }}
                value={localFilters.date_to}
                onChange={(e) => setLocalFilters((prev) => ({ ...prev, date_to: e.target.value }))}
                InputLabelProps={{ shrink: true }}
              />
            </Grid>
          </Grid>

          <Stack direction="row" spacing={1} sx={{ mt: 1.5 }}>
            <Button variant="contained" size="small" onClick={applyFilters}>
              Apply
            </Button>
            <Button
              variant="outlined"
              size="small"
              onClick={resetFilters}
              startIcon={<ClearIcon fontSize="small" />}
              disabled={!hasActiveFilters}
            >
              Reset
            </Button>
          </Stack>
        </Paper>

        <Paper variant="outlined" sx={{ borderRadius: 2, overflow: 'hidden' }}>
          <TableContainer>
            <Table size="small">
              <TableHead>
                <TableRow>
                  <TableCell sx={{ fontWeight: 700, width: 70 }}>ID</TableCell>
                  <TableCell sx={{ fontWeight: 700, minWidth: 180 }}>Admin</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 110 }}>Action</TableCell>
                  <TableCell sx={{ fontWeight: 700, minWidth: 220 }}>Route</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 90 }}>Status</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 200 }}>Time</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 70 }}>Data</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {rows.map((row) => {
                  const expanded = Number(expandedId) === Number(row.id);
                  const payloadText = JSON.stringify(row.request_payload || {}, null, 2);
                  return (
                    <React.Fragment key={`log-${row.id}`}>
                      <TableRow hover>
                        <TableCell>{row.id}</TableCell>
                        <TableCell>
                          <Typography variant="body2" sx={{ fontWeight: 600 }}>
                            {row.admin_name || `Admin #${row.admin_id || '-'}`}
                          </Typography>
                          <Typography variant="caption" color="text.secondary">
                            {row.admin_email || '-'}
                          </Typography>
                        </TableCell>
                        <TableCell>
                          <Stack direction="row" spacing={0.5}>
                            <Chip size="small" color={methodColor(row.method)} label={row.method || '-'} />
                            <Chip size="small" color={actionColor(row.action)} label={row.action || '-'} />
                          </Stack>
                        </TableCell>
                        <TableCell>
                          <Tooltip title={row.path || '-'}>
                            <Typography variant="body2" noWrap sx={{ maxWidth: 280 }}>
                              {row.route_name || row.path || '-'}
                            </Typography>
                          </Tooltip>
                          <Typography variant="caption" color="text.secondary" noWrap sx={{ display: 'block', maxWidth: 280 }}>
                            {row.path || '-'}
                          </Typography>
                        </TableCell>
                        <TableCell>{row.status_code ?? '-'}</TableCell>
                        <TableCell>{formatDateTime(row.created_at)}</TableCell>
                        <TableCell>
                          <IconButton
                            size="small"
                            onClick={() => setExpandedId(expanded ? null : row.id)}
                            sx={{ transform: expanded ? 'rotate(180deg)' : 'rotate(0deg)', transition: 'transform 0.2s' }}
                          >
                            <ExpandMoreIcon fontSize="small" />
                          </IconButton>
                        </TableCell>
                      </TableRow>
                      <TableRow>
                        <TableCell colSpan={7} sx={{ p: 0, borderBottom: expanded ? '1px solid' : 0, borderColor: 'divider' }}>
                          <Collapse in={expanded} timeout="auto" unmountOnExit>
                            <Box sx={{ p: 1.5, bgcolor: 'background.default' }}>
                              <Typography variant="caption" sx={{ fontWeight: 700, display: 'block', mb: 0.75 }}>
                                Request Payload
                              </Typography>
                              <Box
                                component="pre"
                                sx={{
                                  m: 0,
                                  p: 1,
                                  borderRadius: 1,
                                  bgcolor: 'background.paper',
                                  border: '1px solid',
                                  borderColor: 'divider',
                                  overflow: 'auto',
                                  fontSize: 12,
                                  lineHeight: 1.45,
                                  maxHeight: 240,
                                }}
                              >
                                {payloadText}
                              </Box>
                            </Box>
                          </Collapse>
                        </TableCell>
                      </TableRow>
                    </React.Fragment>
                  );
                })}
                {rows.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={7}>
                      <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                        No activity logs found.
                      </Typography>
                    </TableCell>
                  </TableRow>
                ) : null}
              </TableBody>
            </Table>
          </TableContainer>

          {Number(logs?.last_page || 1) > 1 ? (
            <Box sx={{ p: 1.5, display: 'flex', justifyContent: 'center' }}>
              <Pagination
                page={Number(logs?.current_page || 1)}
                count={Number(logs?.last_page || 1)}
                onChange={onPageChange}
                color="primary"
                size="small"
              />
            </Box>
          ) : null}
        </Paper>
      </Stack>
    </Box>
  );
}

ActivityLogs.layout = (page) => <AdminLayout children={page} />;
