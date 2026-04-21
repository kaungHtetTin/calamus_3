import React from 'react';
import { Box, Button, Checkbox, Chip, FormControlLabel, Paper, Stack, TextField, Typography } from '@mui/material';
import { UploadFile as UploadFileIcon } from '@mui/icons-material';

export default function BulkDocumentLessonCreateForm({
  lessonData,
  lessonErrors,
  setLessonData,
  uploadItems,
  uploading,
  handleBulkDocumentFilesChange,
}) {
  return (
    <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: 'repeat(2, minmax(0, 1fr))' }, gap: 1.5 }}>
      <TextField
        label="Mini Title"
        value={lessonData.title_mini}
        onChange={(event) => setLessonData('title_mini', event.target.value)}
        error={Boolean(lessonErrors.title_mini)}
        helperText={lessonErrors.title_mini}
      />
      <FormControlLabel
        control={<Checkbox checked={Boolean(lessonData.isVip)} onChange={(event) => setLessonData('isVip', event.target.checked)} />}
        label="VIP Lesson"
      />
      <Paper variant="outlined" sx={{ p: 1.5, borderRadius: 1.5, borderStyle: 'dashed', borderColor: lessonErrors.html_files ? 'error.main' : 'divider', gridColumn: { xs: 'span 1', md: 'span 2' } }}>
        <Stack direction="row" spacing={1.25} justifyContent="space-between" alignItems="center">
          <Box>
            <Typography variant="caption" sx={{ fontWeight: 700, display: 'block' }}>
              HTML Files
            </Typography>
            <Typography variant="caption" color="text.secondary">
              {uploadItems.length ? `${uploadItems.length} file(s) selected` : 'No files selected'}
            </Typography>
          </Box>
          <Button component="label" size="small" variant="outlined" startIcon={<UploadFileIcon />} disabled={uploading}>
            Upload HTML Files
            <input hidden type="file" accept=".html,.htm,text/html" multiple onChange={handleBulkDocumentFilesChange} />
          </Button>
        </Stack>
        {uploadItems.length > 0 && (
          <Stack spacing={0.75} sx={{ mt: 1 }}>
            {uploadItems.map((item, index) => (
              <Stack key={`bulk-file-${index}`} direction="row" spacing={1} alignItems="center" justifyContent="space-between">
                <Typography variant="caption" color="text.secondary" sx={{ minWidth: 0, flex: 1 }} noWrap>
                  {index + 1}. {item.name}
                </Typography>
                <Stack direction="row" spacing={0.5} alignItems="center">
                  <Typography variant="caption" color="text.secondary">
                    {item.progress}%
                  </Typography>
                  <Chip
                    size="small"
                    label={item.status}
                    color={item.status === 'done' ? 'success' : item.status === 'error' ? 'error' : item.status === 'uploading' ? 'primary' : 'default'}
                  />
                </Stack>
              </Stack>
            ))}
          </Stack>
        )}
        {lessonErrors.html_files && (
          <Typography variant="caption" color="error.main" sx={{ display: 'block', mt: 0.75 }}>
            {lessonErrors.html_files}
          </Typography>
        )}
      </Paper>
    </Box>
  );
}
