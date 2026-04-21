import React from 'react';
import { Box, Button, Checkbox, FormControlLabel, Paper, Stack, TextField, Typography } from '@mui/material';
import { UploadFile as UploadFileIcon } from '@mui/icons-material';

export default function DocumentLessonCreateForm({
  lessonData,
  lessonErrors,
  setLessonData,
  documentFileName,
  handleDocumentLessonFileChange,
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
      <Paper variant="outlined" sx={{ p: 1.5, borderRadius: 1.5, borderStyle: 'dashed', borderColor: lessonErrors.html_file ? 'error.main' : 'divider', gridColumn: { xs: 'span 1', md: 'span 2' } }}>
        <Stack direction="row" spacing={1.25} justifyContent="space-between" alignItems="center">
          <Box>
            <Typography variant="caption" sx={{ fontWeight: 700, display: 'block' }}>
              Document File (HTML)
            </Typography>
            <Typography variant="caption" color="text.secondary">
              {documentFileName || lessonData.document_link || 'No file selected'}
            </Typography>
          </Box>
          <Button component="label" size="small" variant="outlined" startIcon={<UploadFileIcon />}>
            Upload HTML
            <input hidden type="file" accept=".html,.htm,text/html" onChange={handleDocumentLessonFileChange} />
          </Button>
        </Stack>
        {lessonErrors.html_file && (
          <Typography variant="caption" color="error.main" sx={{ display: 'block', mt: 0.75 }}>
            {lessonErrors.html_file}
          </Typography>
        )}
      </Paper>
      <FormControlLabel
        control={<Checkbox checked={Boolean(lessonData.isVip)} onChange={(event) => setLessonData('isVip', event.target.checked)} />}
        label="VIP Lesson"
      />
    </Box>
  );
}
