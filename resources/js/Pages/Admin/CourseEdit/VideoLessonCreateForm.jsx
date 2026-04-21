import React from 'react';
import { Avatar, Box, Button, Checkbox, FormControlLabel, Paper, Stack, TextField, Typography } from '@mui/material';
import { UploadFile as UploadFileIcon } from '@mui/icons-material';
import LectureNotesSection from './LectureNotesSection';

export default function VideoLessonCreateForm({
  lessonData,
  lessonErrors,
  setLessonData,
  createLessonVideoName,
  handleCreateLessonVideoChange,
  createLessonThumbPreview,
  createLessonThumbName,
  handleCreateLessonThumbnailChange,
  lectureNotes,
  onAddLectureNote,
  onRemoveLectureNote,
  onChangeLectureNote,
}) {
  return (
    <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: 'repeat(2, minmax(0, 1fr))' }, gap: 1.5 }}>
      <TextField
        label="Title"
        value={lessonData.title}
        onChange={(event) => setLessonData('title', event.target.value)}
        error={Boolean(lessonErrors.title)}
        helperText={lessonErrors.title}
      />
      <TextField
        label="Mini Title"
        value={lessonData.title_mini}
        onChange={(event) => setLessonData('title_mini', event.target.value)}
        error={Boolean(lessonErrors.title_mini)}
        helperText={lessonErrors.title_mini}
      />
      <Paper variant="outlined" sx={{ p: 1.5, borderRadius: 1.5, borderStyle: 'dashed', borderColor: lessonErrors.video_file ? 'error.main' : 'divider' }}>
        <Stack direction="row" spacing={1.25} justifyContent="space-between" alignItems="center">
          <Box>
            <Typography variant="caption" sx={{ fontWeight: 700, display: 'block' }}>
              Video File
            </Typography>
            <Typography variant="caption" color="text.secondary">
              {createLessonVideoName || 'No file selected'}
            </Typography>
          </Box>
          <Button component="label" size="small" variant="outlined" startIcon={<UploadFileIcon />}>
            Upload Video
            <input hidden type="file" accept="video/*" onChange={handleCreateLessonVideoChange} />
          </Button>
        </Stack>
        {lessonErrors.video_file && (
          <Typography variant="caption" color="error.main" sx={{ display: 'block', mt: 0.75 }}>
            {lessonErrors.video_file}
          </Typography>
        )}
      </Paper>
      <Paper variant="outlined" sx={{ p: 1.5, borderRadius: 1.5, borderStyle: 'dashed', borderColor: lessonErrors.thumbnail_image ? 'error.main' : 'divider' }}>
        <Stack direction={{ xs: 'column', sm: 'row' }} spacing={1.25} justifyContent="space-between" alignItems={{ xs: 'flex-start', sm: 'center' }}>
          <Stack direction="row" spacing={1.25} alignItems="center">
            <Avatar
              src={createLessonThumbPreview || lessonData.thumbnail || ''}
              variant="rounded"
              sx={{ width: 80, height: 45, bgcolor: 'action.hover' }}
            />
            <Box>
              <Typography variant="caption" sx={{ fontWeight: 700, display: 'block' }}>
                Thumbnail (16:9)
              </Typography>
              <Typography variant="caption" color="text.secondary">
                {createLessonThumbName || 'No file selected'}
              </Typography>
            </Box>
          </Stack>
          <Button component="label" size="small" variant="outlined" startIcon={<UploadFileIcon />}>
            Upload Thumbnail
            <input hidden type="file" accept="image/*" onChange={handleCreateLessonThumbnailChange} />
          </Button>
        </Stack>
        {lessonErrors.thumbnail_image && (
          <Typography variant="caption" color="error.main" sx={{ display: 'block', mt: 0.75 }}>
            {lessonErrors.thumbnail_image}
          </Typography>
        )}
      </Paper>
      <FormControlLabel
        control={<Checkbox checked={Boolean(lessonData.isVip)} onChange={(event) => setLessonData('isVip', event.target.checked)} />}
        label="VIP Lesson"
      />
      <LectureNotesSection
        lectureNotes={lectureNotes}
        onAddLectureNote={onAddLectureNote}
        onRemoveLectureNote={onRemoveLectureNote}
        onChangeLectureNote={onChangeLectureNote}
      />
    </Box>
  );
}
