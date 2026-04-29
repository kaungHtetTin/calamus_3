import React, { useMemo, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import {
  Alert,
  Box,
  Button,
  Chip,
  Divider,
  MenuItem,
  Paper,
  Stack,
  TextField,
  Typography,
} from '@mui/material';

export default function VipAccessTransfer({ sourceQuery, targetQuery, sourceUser, targetUser, preview }) {
  const { admin_app_url, flash } = usePage().props;
  const [source, setSource] = useState(sourceQuery || '');
  const [target, setTarget] = useState(targetQuery || '');
  const [mode, setMode] = useState('move');
  const [submitting, setSubmitting] = useState(false);

  const canTransfer = useMemo(() => {
    return Boolean(sourceUser?.user_id) && Boolean(targetUser?.user_id) && Number(sourceUser.user_id) !== Number(targetUser.user_id);
  }, [sourceUser, targetUser]);

  const hasVip = Boolean(preview?.source_has_vip);

  const search = () => {
    router.get(
      `${admin_app_url}/users/vip-transfer`,
      {
        source,
        target,
      },
      { preserveScroll: true, preserveState: true }
    );
  };

  const execute = () => {
    if (!canTransfer) return;
    if (!hasVip) return;
    setSubmitting(true);
    router.post(
      `${admin_app_url}/users/vip-transfer`,
      {
        sourceUserId: String(sourceUser.user_id),
        targetUserId: String(targetUser.user_id),
        mode,
      },
      {
        preserveScroll: true,
        onFinish: () => setSubmitting(false),
      }
    );
  };

  const renderUserCard = (label, user) => {
    if (!user) {
      return (
        <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
          <Typography variant="subtitle2" sx={{ fontWeight: 800 }}>
            {label}
          </Typography>
          <Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>
            Not found.
          </Typography>
        </Paper>
      );
    }

    return (
      <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
        <Stack spacing={0.75}>
          <Typography variant="subtitle2" sx={{ fontWeight: 800 }}>
            {label}
          </Typography>
          <Stack direction="row" spacing={1} alignItems="center" sx={{ flexWrap: 'wrap' }}>
            <Chip size="small" label={`ID ${user.user_id}`} />
            {user.email ? <Chip size="small" label={user.email} /> : null}
            {user.phone ? <Chip size="small" label={user.phone} /> : null}
          </Stack>
          <Typography variant="body2" sx={{ fontWeight: 700 }}>
            {user.name || `User ${user.user_id}`}
          </Typography>
        </Stack>
      </Paper>
    );
  };

  return (
    <Box>
      <Head title="VIP Transfer" />

      <Stack spacing={1.5}>
        <Box>
          <Typography variant="h5" sx={{ fontWeight: 700 }}>
            VIP Access Transfer
          </Typography>
          <Typography variant="body2" color="text.secondary">
            Transfer or copy VIP access from one account to another.
          </Typography>
        </Box>

        {flash?.success ? (
          <Alert severity="success" variant="filled">
            {flash.success}
          </Alert>
        ) : null}

        <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
          <Stack spacing={1.25}>
            <Stack direction={{ xs: 'column', md: 'row' }} spacing={1}>
              <TextField
                label="Source (email or phone or user id)"
                value={source}
                onChange={(e) => setSource(e.target.value)}
                size="small"
                fullWidth
              />
              <TextField
                label="Target (email or phone or user id)"
                value={target}
                onChange={(e) => setTarget(e.target.value)}
                size="small"
                fullWidth
              />
              <Button variant="contained" onClick={search} sx={{ minWidth: 120 }}>
                Search
              </Button>
            </Stack>

            <Stack direction={{ xs: 'column', md: 'row' }} spacing={1.5}>
              <Box sx={{ flex: 1 }}>{renderUserCard('Source Account', sourceUser)}</Box>
              <Box sx={{ flex: 1 }}>{renderUserCard('Target Account', targetUser)}</Box>
            </Stack>

            <Divider />

            {canTransfer ? (
              <Stack spacing={1}>
                {!hasVip && !flash?.success ? (
                  <Alert severity="warning">
                    Source account has no VIP access to transfer (no VIP majors, no diamond plan, and no VIP courses).
                  </Alert>
                ) : null}

                {hasVip ? (
                  <Paper variant="outlined" sx={{ p: 1.5, borderRadius: 2 }}>
                    <Stack direction={{ xs: 'column', md: 'row' }} spacing={1} alignItems={{ md: 'center' }} justifyContent="space-between">
                      <Stack spacing={0.25}>
                        <Typography variant="subtitle2" sx={{ fontWeight: 800 }}>
                          Source VIP Summary
                        </Typography>
                        <Typography variant="caption" color="text.secondary">
                          VIP majors: {(preview?.source?.vip_majors || []).join(', ') || '-'} • Diamond majors:{' '}
                          {(preview?.source?.diamond_majors || []).join(', ') || '-'} • VIP courses:{' '}
                          {Number(preview?.source?.vip_courses_count || 0)}
                        </Typography>
                      </Stack>

                      <Stack direction="row" spacing={1} alignItems="center">
                        <TextField
                          select
                          label="Mode"
                          size="small"
                          value={mode}
                          onChange={(e) => setMode(e.target.value)}
                          sx={{ minWidth: 180 }}
                        >
                          <MenuItem value="move">Move (remove from source)</MenuItem>
                          <MenuItem value="copy">Copy (keep on source)</MenuItem>
                        </TextField>
                        <Button variant="contained" color="primary" onClick={execute} disabled={!hasVip || submitting}>
                          {mode === 'move' ? 'Transfer' : 'Copy'}
                        </Button>
                      </Stack>
                    </Stack>
                  </Paper>
                ) : null}
              </Stack>
            ) : (
              <Alert severity="info">
                Search both source and target accounts. Source and target must be different.
              </Alert>
            )}
          </Stack>
        </Paper>
      </Stack>
    </Box>
  );
}

VipAccessTransfer.layout = (page) => <AdminLayout children={page} />;
