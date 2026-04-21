import React, { useEffect, useMemo, useRef } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import { Alert, Box, Button, Chip, Paper, Stack, Typography } from '@mui/material';

export default function LessonHtmlEdit({ course, category, lesson, filePath, htmlContent }) {
  const { admin_app_url, flash } = usePage().props;
  const { data, setData, patch, processing, errors } = useForm({
    html_content: htmlContent || '',
  });
  const isDirty = data.html_content !== (htmlContent || '');
  const editorRef = useRef(null);
  const lineRef = useRef(null);
  const htmlStats = useMemo(() => {
    const content = String(data.html_content || '');
    const lineCount = content.length ? content.split(/\r\n|\r|\n/).length : 1;
    return {
      characters: content.length,
      lines: lineCount,
    };
  }, [data.html_content]);

  const submit = (event) => {
    event.preventDefault();
    patch(`${admin_app_url}/courses/${course.course_id}/categories/${category.id}/lessons/${lesson.id}/html`, data);
  };
  const resetContent = () => {
    setData('html_content', htmlContent || '');
  };
  const lineNumbers = useMemo(
    () => Array.from({ length: htmlStats.lines }, (_, index) => index + 1).join('\n'),
    [htmlStats.lines]
  );
  const handleEditorScroll = (event) => {
    if (lineRef.current) {
      lineRef.current.scrollTop = event.target.scrollTop;
    }
  };
  const handleEditorKeyDown = (event) => {
    if (event.key !== 'Tab') {
      return;
    }
    event.preventDefault();
    const target = event.currentTarget;
    const start = target.selectionStart;
    const end = target.selectionEnd;
    const content = String(data.html_content || '');
    const next = `${content.slice(0, start)}  ${content.slice(end)}`;
    setData('html_content', next);
    requestAnimationFrame(() => {
      if (editorRef.current) {
        editorRef.current.selectionStart = start + 2;
        editorRef.current.selectionEnd = start + 2;
      }
    });
  };
  useEffect(() => {
    const onKeyDown = (event) => {
      if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
        event.preventDefault();
        if (!processing) {
          patch(`${admin_app_url}/courses/${course.course_id}/categories/${category.id}/lessons/${lesson.id}/html`, data);
        }
      }
    };
    window.addEventListener('keydown', onKeyDown);
    return () => window.removeEventListener('keydown', onKeyDown);
  }, [admin_app_url, category.id, course.course_id, data, lesson.id, patch, processing]);

  return (
    <AdminLayout>
      <Head title={`Edit HTML - ${lesson.title}`} />
      <Stack spacing={1.5}>
        {Boolean(flash?.success) && <Alert severity="success">{flash.success}</Alert>}
        <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
          <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1 }}>
            <Typography variant="h6" sx={{ fontWeight: 700 }}>Edit Document HTML</Typography>
            <Button component={Link} href={`${admin_app_url}/courses/${course.course_id}/edit`} variant="outlined" size="small">
              Back to Course
            </Button>
          </Stack>
          <Stack spacing={0.5} sx={{ mb: 1.5 }}>
            <Typography variant="body2"><strong>Course:</strong> {course.title}</Typography>
            <Typography variant="body2"><strong>Category:</strong> {category.category_title}</Typography>
            <Typography variant="body2"><strong>Lesson:</strong> {lesson.title}</Typography>
            <Typography variant="caption" color="text.secondary">{filePath}</Typography>
          </Stack>
          <Box component="form" onSubmit={submit}>
            <Stack direction="row" spacing={0.75} alignItems="center" justifyContent="space-between" sx={{ mb: 0.75 }}>
              <Stack direction="row" spacing={0.75} alignItems="center">
                <Chip size="small" label={`${htmlStats.lines} lines`} />
                <Chip size="small" label={`${htmlStats.characters} chars`} />
                {isDirty && <Chip size="small" color="warning" label="Unsaved changes" />}
              </Stack>
              <Typography variant="caption" color="text.secondary">Ctrl/Cmd + S to save</Typography>
            </Stack>
            <Paper variant="outlined" sx={{ borderRadius: 1.5, overflow: 'hidden' }}>
              <Box sx={{ display: 'grid', gridTemplateColumns: '56px minmax(0,1fr)', height: 540, bgcolor: '#0f172a' }}>
                <Box
                  ref={lineRef}
                  sx={{
                    p: 1.25,
                    pt: 1.5,
                    borderRight: '1px solid',
                    borderColor: 'rgba(148,163,184,0.28)',
                    fontFamily: 'monospace',
                    fontSize: 13,
                    lineHeight: 1.6,
                    color: 'rgba(203,213,225,0.85)',
                    whiteSpace: 'pre',
                    overflow: 'hidden',
                    userSelect: 'none',
                  }}
                >
                  {lineNumbers}
                </Box>
                <Box
                  ref={editorRef}
                  component="textarea"
                  value={data.html_content}
                  onChange={(event) => setData('html_content', event.target.value)}
                  onScroll={handleEditorScroll}
                  onKeyDown={handleEditorKeyDown}
                  spellCheck={false}
                  sx={{
                    width: '100%',
                    height: '100%',
                    p: 1.25,
                    pt: 1.5,
                    m: 0,
                    border: 0,
                    outline: 0,
                    resize: 'none',
                    bgcolor: 'transparent',
                    color: '#e2e8f0',
                    fontFamily: 'monospace',
                    fontSize: 13.5,
                    lineHeight: 1.6,
                  }}
                />
              </Box>
            </Paper>
            {Boolean(errors.html_content) && (
              <Typography variant="caption" color="error.main">
                {errors.html_content}
              </Typography>
            )}
            <Stack direction="row" spacing={1} justifyContent="flex-end" sx={{ mt: 1.25 }}>
              <Button variant="outlined" size="small" onClick={resetContent} disabled={processing || !isDirty}>
                Reset
              </Button>
              <Button
                component={Link}
                href={lesson.document_link || '#'}
                target="_blank"
                rel="noreferrer"
                variant="outlined"
                size="small"
                disabled={!lesson.document_link}
              >
                Open File
              </Button>
              <Button type="submit" variant="contained" disabled={processing || !isDirty}>
                Save HTML
              </Button>
            </Stack>
          </Box>
        </Paper>
      </Stack>
    </AdminLayout>
  );
}
