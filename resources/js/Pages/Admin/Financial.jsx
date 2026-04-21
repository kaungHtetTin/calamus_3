import React from 'react';
import { Head, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import {
  Avatar,
  Alert,
  Box,
  IconButton,
  Paper,
  Snackbar,
  Stack,
  Typography,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Chip,
} from '@mui/material';
import {
  OpenInNew as OpenInNewIcon,
  Payments as PaymentsIcon,
} from '@mui/icons-material';

export default function Financial({ languages = [] }) {
  const { admin_app_url, flash } = usePage().props;
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

  const openLanguageWorkspace = (major) => {
    window.location.href = `${admin_app_url}/financial/workspace?major=${encodeURIComponent(major)}`;
  };

  return (
    <Box>
      <Head title="Financial Management" />
      <Stack spacing={2.5}>
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              Financial Management
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Select a channel/major to manage finance data.
            </Typography>
          </Box>
        </Box>

        <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
          <Table size="small">
            <TableHead>
              <TableRow>
                <TableCell sx={{ fontWeight: 700 }}>Language / Channel</TableCell>
                <TableCell sx={{ fontWeight: 700 }}>Code</TableCell>
                <TableCell align="right" sx={{ fontWeight: 700 }}>Workspace</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {languages.map((language) => (
                <TableRow
                  key={language.code}
                  hover
                  sx={{ cursor: 'pointer' }}
                  onClick={() => openLanguageWorkspace(language.code)}
                >
                  <TableCell>
                    <Stack direction="row" spacing={1.25} alignItems="center">
                      <Avatar
                        src={buildImageUrl(language.image_path)}
                        sx={{
                          width: 28,
                          height: 28,
                          bgcolor: language.primary_color || 'action.selected',
                        }}
                      >
                        <PaymentsIcon fontSize="small" color="action" />
                      </Avatar>
                      <Box>
                        <Typography variant="body2" sx={{ fontWeight: 700 }}>
                          {language.display_name || language.name || language.code}
                        </Typography>
                        <Typography variant="caption" color="text.secondary">
                          {language.module_code ? `Module: ${language.module_code}` : ' '}
                        </Typography>
                      </Box>
                    </Stack>
                  </TableCell>
                  <TableCell>
                    <Stack direction="row" spacing={0.75} alignItems="center">
                      <Chip size="small" label={language.code} variant="outlined" />
                      {language.module_code && <Chip size="small" label={language.module_code} variant="outlined" />}
                    </Stack>
                  </TableCell>
                  <TableCell align="right">
                    <IconButton
                      size="small"
                      onClick={(event) => {
                        event.stopPropagation();
                        openLanguageWorkspace(language.code);
                      }}
                      title="Open Workspace"
                    >
                      <OpenInNewIcon fontSize="small" />
                    </IconButton>
                  </TableCell>
                </TableRow>
              ))}
              {languages.length === 0 && (
                <TableRow>
                  <TableCell colSpan={3}>
                    <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                      No active languages found.
                    </Typography>
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </TableContainer>
      </Stack>
      <Snackbar open={Boolean(flash?.success)} autoHideDuration={3000} anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}>
        <Alert severity="success" variant="filled">
          {flash?.success || 'Action completed successfully.'}
        </Alert>
      </Snackbar>
    </Box>
  );
}

Financial.layout = (page) => <AdminLayout children={page} />;

