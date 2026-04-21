import React, { useMemo, useState } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import {
  Alert,
  Avatar,
  Box,
  Button,
  Card,
  CardContent,
  IconButton,
  Checkbox,
  Chip,
  Divider,
  FormControlLabel,
  Paper,
  Snackbar,
  Stack,
  TextField,
  Typography,
  useTheme,
} from '@mui/material';
import {
  Notifications as NotificationsIcon,
  Language as LanguageIcon,
  MoreVert as MoreVertIcon,
} from '@mui/icons-material';

export default function PushTopic({ languages = [] }) {
  const { admin_app_url, flash } = usePage().props;
  const theme = useTheme();
  const [openSnackbar, setOpenSnackbar] = useState(false);

  const availableLanguages = useMemo(() => {
    return (Array.isArray(languages) ? languages : []).map((l) => ({
      id: l.id,
      name: l.name || '',
      displayName: l.display_name || l.name || '',
      image: l.image_path || '',
      topic: l.firebase_topic_user || '',
      active: Number(l.is_active || 0) === 1,
    }));
  }, [languages]);

  const topicEnabledLanguages = useMemo(
    () => availableLanguages.filter((l) => l.topic.trim() !== ''),
    [availableLanguages]
  );

  const [selectAll, setSelectAll] = useState(true);

  const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
    title: '',
    body: '',
    image: '',
    language_ids: topicEnabledLanguages.map((l) => l.id),
  });

  const selectedIds = Array.isArray(data.language_ids) ? data.language_ids : [];

  const totalSelected = selectedIds.length;

  const toggleLanguage = (id, checked) => {
    const set = new Set(selectedIds);
    if (checked) set.add(id);
    else set.delete(id);
    const next = Array.from(set);
    setData('language_ids', next);
    if (next.length === topicEnabledLanguages.length) {
      setSelectAll(true);
    } else {
      setSelectAll(false);
    }
  };

  const toggleSelectAll = (checked) => {
    setSelectAll(checked);
    setData('language_ids', checked ? topicEnabledLanguages.map((l) => l.id) : []);
  };

  React.useEffect(() => {
    setSelectAll(selectedIds.length > 0 && selectedIds.length === topicEnabledLanguages.length);
  }, [selectedIds.length, topicEnabledLanguages.length]);

  const handleSubmit = (e) => {
    e.preventDefault();
    clearErrors();
    post(`${admin_app_url}/users/push`, {
      onSuccess: () => {
        setOpenSnackbar(true);
      },
    });
  };

  return (
    <AdminLayout>
      <Head title="Push Notification" />
      <Stack spacing={2}>
        <Stack
          direction={{ xs: 'column', md: 'row' }}
          justifyContent="space-between"
          alignItems={{ xs: 'flex-start', md: 'center' }}
          spacing={1.5}
          sx={{ mb: 2 }}
        >
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              Push Notification
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Send a notification to all users subscribed to selected language topics.
            </Typography>
          </Box>
          <Stack direction="row" spacing={1} alignItems="center">
            <Chip icon={<LanguageIcon fontSize="small" />} label={`${totalSelected} selected`} variant="outlined" />
            <FormControlLabel
              control={
                <Checkbox
                  checked={selectAll}
                  onChange={(e) => toggleSelectAll(e.target.checked)}
                  size="small"
                />
              }
              label="Select all"
            />
          </Stack>
        </Stack>

        {flash?.error && <Alert severity="error">{flash.error}</Alert>}
        {flash?.success && <Alert severity="success">{flash.success}</Alert>}

        <Box
          sx={{
            display: 'grid',
            gridTemplateColumns: {
              xs: '1fr',
              md: 'minmax(0, 1fr) clamp(260px, 28vw, 360px)',
            },
            gap: 1.5,
            alignItems: 'start',
          }}
        >
            <Paper variant="outlined" sx={{ p: 2, borderRadius: 2, boxShadow: 'none' }}>
              <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1.25 }}>
                <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
                  Message
                </Typography>
              </Stack>
              <Divider sx={{ mb: 1.5 }} />
              <Stack component="form" spacing={1.5} onSubmit={handleSubmit}>
                <TextField
                  size="small"
                  label="Title"
                  value={data.title}
                  onChange={(e) => setData('title', e.target.value)}
                  error={Boolean(errors?.title)}
                  helperText={errors?.title}
                  fullWidth
                />
                <TextField
                  size="small"
                  label="Message"
                  value={data.body}
                  onChange={(e) => setData('body', e.target.value)}
                  error={Boolean(errors?.body)}
                  helperText={errors?.body}
                  fullWidth
                  multiline
                  minRows={5}
                />
                <TextField
                  size="small"
                  label="Image URL (optional)"
                  value={data.image}
                  onChange={(e) => setData('image', e.target.value)}
                  error={Boolean(errors?.image)}
                  helperText={errors?.image}
                  fullWidth
                />
                {Boolean(errors?.language_ids) && <Alert severity="error">{errors.language_ids}</Alert>}
                <Stack direction="row" spacing={1}>
                  <Button type="submit" size="small" variant="contained" disabled={processing || totalSelected === 0}>
                    Send Notification
                  </Button>
                  <Button
                    type="button"
                    size="small"
                    variant="text"
                    onClick={() => {
                      reset('title', 'body', 'image');
                    }}
                  >
                    Clear
                  </Button>
                </Stack>
              </Stack>
            </Paper>
            <Paper variant="outlined" sx={{ p: 2, borderRadius: 2, boxShadow: 'none' }}>
              <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1.25 }}>
                <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
                  Target Languages
                </Typography>
              </Stack>
              <Divider sx={{ mb: 1.25 }} />
              <Stack spacing={1}>
                {topicEnabledLanguages.length === 0 && (
                  <Alert severity="warning">No languages with a configured firebase_topic_user.</Alert>
                )}
                {topicEnabledLanguages.map((lang) => {
                  const checked = selectedIds.includes(lang.id);
                  return (
                    <Card key={lang.id} variant="outlined">
                      <CardContent sx={{ p: 1.25, '&:last-child': { pb: 1.25 } }}>
                        <Stack direction="row" spacing={1} alignItems="center" justifyContent="space-between">
                          <Stack direction="row" spacing={1} alignItems="center">
                            <Avatar src={lang.image || ''} sx={{ width: 28, height: 28 }} />
                            <Box>
                              <Typography variant="body2" sx={{ fontWeight: 700 }}>
                                {lang.displayName}
                              </Typography>
                              <Typography variant="caption" color="text.secondary">
                                {lang.topic ? `Topic: ${lang.topic}` : 'No topic'}
                              </Typography>
                            </Box>
                          </Stack>
                          <Checkbox
                            checked={checked}
                            onChange={(e) => toggleLanguage(lang.id, e.target.checked)}
                            size="small"
                          />
                        </Stack>
                      </CardContent>
                    </Card>
                  );
                })}
              </Stack>
            </Paper>
        </Box>
      </Stack>

      <Snackbar
        open={openSnackbar}
        autoHideDuration={3000}
        onClose={() => setOpenSnackbar(false)}
        message="Push request submitted"
      />
    </AdminLayout>
  );
}
