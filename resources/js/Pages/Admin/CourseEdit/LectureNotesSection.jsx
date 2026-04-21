import React from 'react';
import { Box, Button, IconButton, Paper, Stack, TextField, Typography } from '@mui/material';
import { Add as AddIcon, Delete as DeleteIcon } from '@mui/icons-material';

export default function LectureNotesSection({
  lectureNotes,
  onAddLectureNote,
  onRemoveLectureNote,
  onChangeLectureNote,
}) {
  const hasItems = lectureNotes.length > 0;

  return (
    <Paper variant="outlined" sx={{ p: 1.5, borderRadius: 1.5, gridColumn: { xs: 'span 1', md: 'span 2' } }}>
      <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1 }}>
        <Typography variant="subtitle2" sx={{ fontWeight: 700 }}>
          Lecture Notes
        </Typography>
        <Button size="small" variant="outlined" startIcon={<AddIcon />} onClick={onAddLectureNote}>
          Add Note
        </Button>
      </Stack>
      <Stack spacing={1}>
        {hasItems ? lectureNotes.map((entry, index) => (
          <Box
            key={`lecture-note-${index}`}
            sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: '80px 80px 80px minmax(0,1fr) auto' }, gap: 1 }}
          >
            <TextField
              type="text"
              inputProps={{ inputMode: 'numeric', pattern: '[0-9]*' }}
              label="Hour"
              value={entry.hour}
              onChange={(event) => onChangeLectureNote(index, 'hour', event.target.value)}
            />
            <TextField
              type="text"
              inputProps={{ inputMode: 'numeric', pattern: '[0-9]*' }}
              label="Minute"
              value={entry.minute}
              onChange={(event) => onChangeLectureNote(index, 'minute', event.target.value)}
            />
            <TextField
              type="text"
              inputProps={{ inputMode: 'numeric', pattern: '[0-9]*' }}
              label="Second"
              value={entry.second}
              onChange={(event) => onChangeLectureNote(index, 'second', event.target.value)}
            />
            <TextField
              label="Note"
              value={entry.note}
              onChange={(event) => onChangeLectureNote(index, 'note', event.target.value)}
            />
            <IconButton
              color="error"
              onClick={() => onRemoveLectureNote(index)}
              sx={{ alignSelf: { xs: 'flex-end', md: 'center' } }}
            >
              <DeleteIcon fontSize="small" />
            </IconButton>
          </Box>
        )) : (
          <Typography variant="caption" color="text.secondary">
            No lecture note rows. Click Add Note to create one.
          </Typography>
        )}
      </Stack>
    </Paper>
  );
}
