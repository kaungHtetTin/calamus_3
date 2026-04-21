import React from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import {
  Alert,
  Box,
  Button,
  Paper,
  Stack,
  TextField,
  Typography,
} from '@mui/material';

export default function SongLyricEdit({ major = '', song = null, lyricText = '' }) {
  const { admin_app_url, flash } = usePage().props;
  const { data, setData, patch, processing, errors } = useForm({
    lyric_text: lyricText || '',
  });

  if (!song) {
    return (
      <Box>
        <Head title="Edit Lyrics" />
        <Typography>Song not found.</Typography>
      </Box>
    );
  }

  const submit = (event) => {
    event.preventDefault();
    patch(`${admin_app_url}/songs/songs/${song.id}/lyric?major=${encodeURIComponent(major)}`, {
      preserveScroll: true,
    });
  };

  return (
    <Box>
      <Head title={`Lyrics: ${song.title}`} />
      <Stack spacing={1.5}>
        <Box>
          <Typography variant="h6" sx={{ fontWeight: 700 }}>
            Edit Lyrics
          </Typography>
          <Typography variant="body2" color="text.secondary">
            {song.title} (ID {song.id}) • {major || '-'}
          </Typography>
        </Box>

        {Boolean(flash?.success) && (
          <Alert severity="success">{flash.success}</Alert>
        )}
        {Boolean(errors?.lyric_text) && (
          <Alert severity="error">{errors.lyric_text}</Alert>
        )}

        <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
          <Box component="form" onSubmit={submit}>
            <Stack spacing={1.25}>
              <TextField
                label="Lyrics (.txt)"
                value={data.lyric_text}
                onChange={(e) => setData('lyric_text', e.target.value)}
                multiline
                minRows={14}
                fullWidth
              />
              <Stack direction="row" spacing={1} justifyContent="flex-end">
                <Button
                  component={Link}
                  href={`${admin_app_url}/songs/workspace?major=${encodeURIComponent(major)}&tab=songs`}
                  variant="outlined"
                  size="small"
                >
                  Back
                </Button>
                <Button type="submit" variant="contained" size="small" disabled={processing}>
                  Save
                </Button>
              </Stack>
            </Stack>
          </Box>
        </Paper>
      </Stack>
    </Box>
  );
}

SongLyricEdit.layout = (page) => <AdminLayout children={page} />;

