import React, { useMemo, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import {
  Alert,
  Box,
  Button,
  Chip,
  Dialog,
  DialogContent,
  Divider,
  IconButton,
  Paper,
  Snackbar,
  Stack,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TablePagination,
  TableRow,
  Typography,
} from '@mui/material';
import { Close as CloseIcon } from '@mui/icons-material';

export default function EnrollCourse({ payments }) {
  const { admin_app_url } = usePage().props;
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'success' });
  const [preview, setPreview] = useState({ open: false, url: '' });

  const rows = useMemo(() => {
    return Array.isArray(payments?.data) ? payments.data : [];
  }, [payments]);

  const total = Number(payments?.total ?? rows.length);
  const perPage = Number(payments?.per_page ?? 25);
  const page = Number(payments?.current_page ?? 1);

  const goToPage = (nextPage) => {
    router.get(
      `${admin_app_url}/users/enroll-course`,
      {
        page: nextPage,
      },
      { preserveState: true, replace: true }
    );
  };

  const handleActivate = (paymentId) => {
    router.post(
      `${admin_app_url}/users/enroll-course/${paymentId}/activate`,
      {},
      {
        preserveScroll: true,
        onSuccess: () => setSnackbar({ open: true, message: 'Activated', severity: 'success' }),
        onError: () => setSnackbar({ open: true, message: 'Activation failed', severity: 'error' }),
      }
    );
  };

  const getScreenshotUrl = (value) => {
    const raw = String(value || '').trim();
    if (!raw) return '';
    if (/^https?:\/\//i.test(raw)) return raw;
    if (raw.startsWith('/')) return raw;
    return `/${raw}`;
  };

  return (
    <Box>
      <Head title="Enroll Course" />

      <Stack spacing={1.5}>
        <Stack direction={{ xs: 'column', md: 'row' }} justifyContent="space-between" alignItems={{ xs: 'flex-start', md: 'center' }} spacing={1}>
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              Enroll Course
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Activate payment records and enroll users into paid courses.
            </Typography>
          </Box>
        </Stack>

        <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
          <Stack direction="row" spacing={1} alignItems="center" justifyContent="space-between" sx={{ mb: 1 }}>
            <Stack direction="row" spacing={1} alignItems="center">
              <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                Payments ({total})
              </Typography>
            </Stack>
          </Stack>

          <Divider sx={{ mb: 1.5 }} />

          <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
            <Table size="small" sx={{ tableLayout: 'auto', '& .MuiTableCell-root': { px: 1, py: 0.75, verticalAlign: 'top' } }}>
              <TableHead>
                <TableRow>
                  <TableCell sx={{ fontWeight: 700, width: 220 }}>User</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 110 }}>Major</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 120 }}>Amount</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 140 }}>Package Plan</TableCell>
                  <TableCell sx={{ fontWeight: 700, minWidth: 240 }}>Courses</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 110 }}>Screenshot</TableCell>
                  <TableCell sx={{ fontWeight: 700, width: 140 }}>Action</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {rows.map((row) => {
                  const activated = Boolean(row.activated);
                  const courseTitles = Array.isArray(row.course_titles) ? row.course_titles : [];
                  const screenshotUrl = getScreenshotUrl(row.screenshot);
                  const userName = String(row.user_name || '').trim();
                  return (
                    <TableRow key={`enroll-pay-${row.id}`} hover>
                      <TableCell>
                        <Stack spacing={0.25}>
                          <Typography variant="body2" sx={{ fontWeight: 700, lineHeight: 1.15 }}>
                            {userName || 'Unknown User'}
                          </Typography>
                          <Typography variant="caption" color="text.secondary" sx={{ lineHeight: 1.1 }}>
                            ID: {row.user_id}
                          </Typography>
                        </Stack>
                      </TableCell>
                      <TableCell>
                        <Typography variant="body2">{row.major || '-'}</Typography>
                      </TableCell>
                      <TableCell>
                        <Typography variant="body2">{row.amount ?? '-'}</Typography>
                      </TableCell>
                      <TableCell>
                        {row.meta?.packagePlan ? (
                          <Chip size="small" label={row.meta.packagePlan} color="primary" variant="outlined" />
                        ) : (
                          <Typography variant="body2" color="text.secondary">-</Typography>
                        )}
                      </TableCell>
                      <TableCell>
                        <Stack direction="row" spacing={0.75} useFlexGap flexWrap="wrap">
                          {courseTitles.map((title) => (
                            <Chip
                              key={`ct-${row.id}-${title}`}
                              size="small"
                              label={title}
                              variant="outlined"
                              sx={{ maxWidth: 220 }}
                            />
                          ))}
                        </Stack>
                      </TableCell>
                      <TableCell>
                        {screenshotUrl ? (
                          <Box
                            component="img"
                            src={screenshotUrl}
                            alt="payment screenshot"
                            onClick={() => setPreview({ open: true, url: screenshotUrl })}
                            sx={{
                              width: 44,
                              height: 44,
                              borderRadius: 1,
                              objectFit: 'cover',
                              border: '1px solid',
                              borderColor: 'divider',
                              cursor: 'pointer',
                              display: 'block',
                            }}
                          />
                        ) : (
                          <Typography variant="body2" color="text.secondary">
                            -
                          </Typography>
                        )}
                      </TableCell>
                      <TableCell>
                        <Button
                          variant="contained"
                          size="small"
                          disabled={activated}
                          onClick={() => handleActivate(row.id)}
                          sx={{ minWidth: 110 }}
                        >
                          Activate
                        </Button>
                      </TableCell>
                    </TableRow>
                  );
                })}
                {rows.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={7}>
                      <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                        No payments found.
                      </Typography>
                    </TableCell>
                  </TableRow>
                ) : null}
              </TableBody>
            </Table>
          </TableContainer>

          <TablePagination
            component="div"
            count={total}
            page={Math.max(0, page - 1)}
            rowsPerPage={perPage}
            rowsPerPageOptions={[25]}
            onPageChange={(_, nextPage) => goToPage(nextPage + 1)}
          />
        </Paper>
      </Stack>

      <Snackbar open={snackbar.open} autoHideDuration={2500} onClose={() => setSnackbar((s) => ({ ...s, open: false }))}>
        <Alert severity={snackbar.severity} variant="filled" onClose={() => setSnackbar((s) => ({ ...s, open: false }))}>
          {snackbar.message}
        </Alert>
      </Snackbar>

      <Dialog open={preview.open} onClose={() => setPreview({ open: false, url: '' })} maxWidth="md" fullWidth>
        <Stack direction="row" alignItems="center" justifyContent="space-between" sx={{ px: 2, py: 1.25 }}>
          <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
            Payment Screenshot
          </Typography>
          <IconButton size="small" onClick={() => setPreview({ open: false, url: '' })} aria-label="close">
            <CloseIcon fontSize="small" />
          </IconButton>
        </Stack>
        <Divider />
        <DialogContent sx={{ p: 2 }}>
          {preview.url ? (
            <Box
              component="img"
              src={preview.url}
              alt="payment screenshot preview"
              sx={{
                width: '100%',
                maxHeight: '70vh',
                objectFit: 'contain',
                borderRadius: 1.5,
                border: '1px solid',
                borderColor: 'divider',
                backgroundColor: 'background.default',
              }}
            />
          ) : null}
        </DialogContent>
      </Dialog>
    </Box>
  );
}

EnrollCourse.layout = (page) => <AdminLayout children={page} />;
