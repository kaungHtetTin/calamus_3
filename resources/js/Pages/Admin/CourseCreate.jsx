import React, { useEffect, useMemo, useRef, useState } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import ImageCropper from '../../Components/Admin/ImageCropper';
import {
  Alert,
  Box,
  Button,
  Chip,
  Divider,
  IconButton,
  MenuItem,
  Paper,
  Stack,
  Step,
  StepLabel,
  Stepper,
  TextField,
  Tooltip,
  Typography,
} from '@mui/material';
import {
  Add as AddIcon,
  FormatBold as FormatBoldIcon,
  FormatItalic as FormatItalicIcon,
  FormatListBulleted as FormatListBulletedIcon,
  FormatListNumbered as FormatListNumberedIcon,
} from '@mui/icons-material';

export default function CourseCreate({ teachers = [], majorOptions = [] }) {
  const STEPS = ['Basic', 'Media', 'Publish'];
  const { admin_app_url } = usePage().props;
  const [webCoverCropperOpen, setWebCoverCropperOpen] = useState(false);
  const [webCoverTempImage, setWebCoverTempImage] = useState(null);
  const [activeStep, setActiveStep] = useState(0);
  const [submitError, setSubmitError] = useState('');
  const [coverFileName, setCoverFileName] = useState('');
  const [webCoverFileName, setWebCoverFileName] = useState('');
  const [previewVideoFileName, setPreviewVideoFileName] = useState('');
  const [previewVideoPreview, setPreviewVideoPreview] = useState('');
  const detailsRef = useRef(null);
  const {
    data,
    setData,
    post,
    processing,
    errors,
  } = useForm({
    teacher_id: '',
    title: '',
    certificate_title: '',
    lessons_count: 0,
    cover_url: '',
    web_cover: '',
    cover_image: null,
    web_cover_image: null,
    description: '',
    details: '',
    is_vip: false,
    active: true,
    duration: 0,
    background_color: '#FFFFFF',
    fee: 0,
    enroll: 0,
    rating: 0,
    major: '',
    sorting: 0,
    preview: '',
    preview_video: null,
    certificate_code: '',
  });

  const submit = (event) => {
    event.preventDefault();
    setSubmitError('');
    post(`${admin_app_url}/courses`, {
      forceFormData: true,
      data: {
        ...data,
        teacher_id: Number(data.teacher_id || 0),
        lessons_count: Number(data.lessons_count || 0),
        duration: Number(data.duration || 0),
        fee: Number(data.fee || 0),
        enroll: Number(data.enroll || 0),
        rating: Number(data.rating || 0),
        sorting: Number(data.sorting || 0),
        is_vip: Boolean(data.is_vip),
        active: Boolean(data.active),
      },
      onError: () => setSubmitError('Please check required fields before creating course.'),
    });
  };
  const validateStep = (stepIndex) => {
    if (stepIndex === 0) {
      if (!String(data.teacher_id || '').trim()) return 'Teacher is required.';
      if (!String(data.title || '').trim()) return 'Course title is required.';
      if (!String(data.certificate_title || '').trim()) return 'Certificate title is required.';
      if (!String(data.certificate_code || '').trim()) return 'Certificate code is required.';
      if (!String(data.major || '').trim()) return 'Major is required.';
      if (!String(data.description || '').trim()) return 'Description is required.';
      if (!String(data.details || '').trim()) return 'Details is required.';
      return '';
    }
    if (stepIndex === 1) {
      const hasCover = Boolean(data.cover_image || String(data.cover_url || '').trim());
      const hasWebCover = Boolean(data.web_cover_image || String(data.web_cover || '').trim());
      if (!hasCover) return 'Cover image is required (URL or upload).';
      if (!hasWebCover) return 'Web cover is required (URL or upload).';
      return '';
    }
    return '';
  };
  const handleNext = () => {
    const err = validateStep(activeStep);
    if (err) {
      setSubmitError(err);
      return;
    }
    setSubmitError('');
    setActiveStep((prev) => Math.min(prev + 1, STEPS.length - 1));
  };
  const handleBack = () => {
    setSubmitError('');
    setActiveStep((prev) => Math.max(prev - 1, 0));
  };

  const handleCoverChange = (event) => {
    const file = event.target.files?.[0] || null;
    setData('cover_image', file);
    setCoverFileName(file ? file.name : '');
  };
  const handleWebCoverChange = (event) => {
    const file = event.target.files?.[0];
    if (!file) {
      return;
    }
    const reader = new FileReader();
    reader.onload = () => {
      setWebCoverTempImage(reader.result);
      setWebCoverCropperOpen(true);
    };
    reader.readAsDataURL(file);
  };
  const handleWebCoverCropComplete = (blob) => {
    const file = new File([blob], `web_cover_${Date.now()}.jpg`, { type: 'image/jpeg' });
    setData('web_cover_image', file);
    setData('web_cover', '');
    setWebCoverFileName(file.name);
    setWebCoverCropperOpen(false);
    setWebCoverTempImage(null);
  };
  const handlePreviewVideoChange = (event) => {
    const file = event.target.files?.[0] || null;
    if (previewVideoPreview) {
      URL.revokeObjectURL(previewVideoPreview);
    }
    setData('preview_video', file);
    setPreviewVideoFileName(file ? file.name : '');
    if (file) {
      setPreviewVideoPreview(URL.createObjectURL(file));
      setData('preview', '');
    } else {
      setPreviewVideoPreview('');
    }
  };

  const runDetailsCommand = (command) => {
    if (!detailsRef.current) {
      return;
    }
    detailsRef.current.focus();
    document.execCommand(command, false, null);
    setData('details', String(detailsRef.current.innerHTML || ''));
  };

  const vimeoEmbedUrl = useMemo(() => {
    const raw = String(data.preview || '').trim();
    if (!raw) {
      return '';
    }
    if (raw.includes('player.vimeo.com/video/')) {
      return raw;
    }
    const match = raw.match(/vimeo\.com\/(?:video\/)?(\d+)/i) || raw.match(/\/videos\/(\d+)/i) || raw.match(/^(\d+)$/);
    if (!match) {
      return '';
    }
    return `https://player.vimeo.com/video/${match[1]}`;
  }, [data.preview]);
  const directPreviewVideo = useMemo(() => {
    if (previewVideoPreview) {
      return previewVideoPreview;
    }
    const raw = String(data.preview || '').trim();
    if (!raw || vimeoEmbedUrl) {
      return '';
    }
    return raw;
  }, [previewVideoPreview, data.preview, vimeoEmbedUrl]);

  useEffect(() => {
    if (!Array.isArray(majorOptions) || majorOptions.length === 0) {
      return;
    }
    const currentMajor = String(data.major || '').trim().toLowerCase();
    const availableMajors = majorOptions.map((option) => String(option.value || '').trim().toLowerCase()).filter(Boolean);
    if (!currentMajor || !availableMajors.includes(currentMajor)) {
      setData('major', availableMajors[0]);
    }
  }, [majorOptions]);

  useEffect(() => {
    if (!detailsRef.current) {
      return;
    }
    const nextValue = String(data.details || '');
    if (detailsRef.current.innerHTML !== nextValue) {
      detailsRef.current.innerHTML = nextValue;
    }
  }, [data.details]);

  return (
      <Box>
      <Head title="Create Course" />
      <Stack spacing={1.5}>
        <Stack direction={{ xs: 'column', md: 'row' }} justifyContent="space-between" alignItems={{ xs: 'flex-start', md: 'center' }} spacing={1.5}>
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>Create Course</Typography>
            <Typography variant="body2" color="text.secondary">Create a new course with media, metadata and certificate settings.</Typography>
          </Box>
          <Stack direction="row" spacing={1}>
            <Button component={Link} href={`${admin_app_url}/courses`} variant="outlined" size="small">Back</Button>
            <Button variant="contained" size="small" startIcon={<AddIcon />} onClick={submit} disabled={processing}>Create Course</Button>
          </Stack>
        </Stack>
        <Paper variant="outlined" sx={{ p: 2, borderRadius: 2, boxShadow: 'none' }}>
          <Stepper
            activeStep={activeStep}
            alternativeLabel
            sx={{
              '& .MuiStepConnector-line': { borderColor: 'divider' },
              '& .MuiStepLabel-label': { fontSize: 13, mt: 0.35 },
              '& .MuiStepIcon-root': {
                color: 'transparent',
                border: '1.5px solid',
                borderColor: 'divider',
                borderRadius: '50%',
              },
              '& .MuiStepIcon-root.Mui-active': {
                color: 'transparent',
                borderColor: 'primary.main',
              },
              '& .MuiStepIcon-root.Mui-completed': {
                color: 'transparent',
                borderColor: 'success.main',
              },
              '& .MuiStepIcon-text': {
                fill: 'text.primary',
                fontSize: 12,
                fontWeight: 700,
              },
            }}
          >
            {STEPS.map((label) => (
              <Step key={label}>
                <StepLabel>{label}</StepLabel>
              </Step>
            ))}
          </Stepper>
        </Paper>
        {Boolean(submitError) && <Alert severity="error">{submitError}</Alert>}

        <Paper variant="outlined" sx={{ p: 2, borderRadius: 2, boxShadow: 'none' }}>
          <Box component="form" onSubmit={submit}>
            <Stack spacing={1.5}>
              {activeStep === 0 && (
                <>
                <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>Basic Information</Typography>
                <Divider />
                <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: 'repeat(2,minmax(0,1fr))' }, gap: 1.5 }}>
                <Box>
                <TextField size="small" fullWidth select label="Teacher" value={String(data.teacher_id || '')} onChange={(event) => setData('teacher_id', event.target.value)} error={Boolean(errors.teacher_id)} helperText={errors.teacher_id}>
                  {teachers.map((teacher) => (
                    <MenuItem key={`teacher-${teacher.id}`} value={String(teacher.id)}>{teacher.name}</MenuItem>
                  ))}
                </TextField>
                </Box>
                <Box>
                <TextField size="small" fullWidth label="Course Title" value={data.title} onChange={(event) => setData('title', event.target.value)} error={Boolean(errors.title)} helperText={errors.title} />
                </Box>
                <Box>
                <TextField size="small" fullWidth label="Certificate Title" value={data.certificate_title} onChange={(event) => setData('certificate_title', event.target.value)} error={Boolean(errors.certificate_title)} helperText={errors.certificate_title} />
                </Box>
                <Box>
                <TextField size="small" fullWidth label="Certificate Code" value={data.certificate_code} onChange={(event) => setData('certificate_code', event.target.value)} error={Boolean(errors.certificate_code)} helperText={errors.certificate_code} />
                </Box>
                <Box>
                <TextField
                  size="small"
                  fullWidth
                  select
                  label="Major"
                  value={String(data.major || '')}
                  onChange={(event) => setData('major', event.target.value)}
                  error={Boolean(errors.major)}
                  helperText={errors.major || (majorOptions.length ? 'Select allowed major scope' : 'No major scope access')}
                  disabled={majorOptions.length === 0}
                >
                  {majorOptions.map((option) => (
                    <MenuItem key={`major-${option.value}`} value={String(option.value)}>
                      {option.label}
                    </MenuItem>
                  ))}
                </TextField>
                </Box>
                <Box>
                <TextField size="small" fullWidth type="number" label="Duration" value={data.duration} onChange={(event) => setData('duration', event.target.value)} error={Boolean(errors.duration)} helperText={errors.duration} />
                </Box>
                <Box>
                <TextField size="small" fullWidth type="number" label="Fee" value={data.fee} onChange={(event) => setData('fee', event.target.value)} error={Boolean(errors.fee)} helperText={errors.fee} />
                </Box>
                <Box>
                <TextField size="small" fullWidth type="number" label="Sorting" value={data.sorting} onChange={(event) => setData('sorting', event.target.value)} error={Boolean(errors.sorting)} helperText={errors.sorting} />
                </Box>
                <Box>
                <TextField size="small" fullWidth select label="VIP Course" value={data.is_vip ? '1' : '0'} onChange={(event) => setData('is_vip', event.target.value === '1')} error={Boolean(errors.is_vip)} helperText={errors.is_vip}>
                  <MenuItem value="1">Yes</MenuItem>
                  <MenuItem value="0">No</MenuItem>
                </TextField>
                </Box>
                <Box>
                <TextField size="small" fullWidth select label="Active" value={data.active ? '1' : '0'} onChange={(event) => setData('active', event.target.value === '1')} error={Boolean(errors.active)} helperText={errors.active}>
                  <MenuItem value="1">Active</MenuItem>
                  <MenuItem value="0">Inactive</MenuItem>
                </TextField>
                </Box>
                <Box>
                <Stack spacing={0.5}>
                  <TextField size="small" fullWidth label="Background Color" value={data.background_color} onChange={(event) => setData('background_color', event.target.value)} error={Boolean(errors.background_color)} helperText={errors.background_color || 'Hex color'} />
                  <Box component="input" type="color" value={String(data.background_color || '#FFFFFF')} onChange={(event) => setData('background_color', event.target.value.toUpperCase())} sx={{ width: 40, height: 28, p: 0, border: '1px solid', borderColor: 'divider', borderRadius: 1 }} />
                </Stack>
                </Box>
              </Box>
              <TextField size="small" fullWidth label="Description" value={data.description} onChange={(event) => setData('description', event.target.value)} error={Boolean(errors.description)} helperText={errors.description} />
              <Paper variant="outlined" sx={{ borderRadius: 2, overflow: 'hidden', boxShadow: 'none' }}>
                <Stack direction="row" spacing={0.5} sx={{ p: 0.75, borderBottom: '1px solid', borderColor: 'divider', bgcolor: 'action.hover' }}>
                  <Tooltip title="Bold"><IconButton size="small" onClick={() => runDetailsCommand('bold')}><FormatBoldIcon fontSize="small" /></IconButton></Tooltip>
                  <Tooltip title="Italic"><IconButton size="small" onClick={() => runDetailsCommand('italic')}><FormatItalicIcon fontSize="small" /></IconButton></Tooltip>
                  <Tooltip title="Bulleted"><IconButton size="small" onClick={() => runDetailsCommand('insertUnorderedList')}><FormatListBulletedIcon fontSize="small" /></IconButton></Tooltip>
                  <Tooltip title="Numbered"><IconButton size="small" onClick={() => runDetailsCommand('insertOrderedList')}><FormatListNumberedIcon fontSize="small" /></IconButton></Tooltip>
                </Stack>
                <Box
                  ref={detailsRef}
                  contentEditable
                  dir="ltr"
                  suppressContentEditableWarning
                  onInput={(event) => setData('details', String(event.currentTarget.innerHTML || ''))}
                  sx={{ minHeight: 190, p: 1.5, outline: 'none', fontSize: 14, lineHeight: 1.5, direction: 'ltr', textAlign: 'left', unicodeBidi: 'plaintext', '& ul, & ol': { pl: 2.5, my: 0.5 }, '& p': { my: 0.5 } }}
                />
                <Typography variant="caption" color={errors.details ? 'error.main' : 'text.secondary'} sx={{ px: 1.5, pb: 1.25 }}>
                  {errors.details || 'Stored as HTML'}
                </Typography>
              </Paper>
                </>
              )}

              {activeStep === 1 && (
              <>
              <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>Media Assets</Typography>
              <Divider />
              <Paper variant="outlined" sx={{ p: 2, borderRadius: 2, boxShadow: 'none' }}>
                <Stack spacing={1}>
                  <TextField size="small" label="Preview Vimeo URL" value={data.preview} onChange={(event) => setData('preview', event.target.value)} error={Boolean(errors.preview)} helperText={errors.preview || 'Optional. Vimeo URL or upload preview video'} />
                  <Stack direction="row" spacing={1} justifyContent="space-between" alignItems="center">
                    <Typography variant="caption" color="text.secondary" noWrap sx={{ maxWidth: 220 }}>{previewVideoFileName || 'No uploaded preview video'}</Typography>
                    <Button component="label" size="small" variant="outlined">Upload Preview Video<input hidden type="file" accept="video/mp4,video/quicktime,video/x-msvideo,video/x-matroska" onChange={handlePreviewVideoChange} /></Button>
                  </Stack>
                  {Boolean(vimeoEmbedUrl) && (
                    <Box sx={{ position: 'relative', width: '100%', pt: '56.25%', borderRadius: 1.25, overflow: 'hidden', bgcolor: 'black' }}>
                      <Box component="iframe" src={vimeoEmbedUrl} allow="autoplay; fullscreen; picture-in-picture" allowFullScreen sx={{ position: 'absolute', inset: 0, width: '100%', height: '100%', border: 0 }} />
                    </Box>
                  )}
                  {Boolean(directPreviewVideo) && (
                    <Box sx={{ borderRadius: 1.25, overflow: 'hidden', bgcolor: 'black', aspectRatio: '16 / 9' }}>
                      <Box component="video" src={directPreviewVideo} controls sx={{ width: '100%', height: '100%' }} />
                    </Box>
                  )}
                </Stack>
              </Paper>

              <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: 'repeat(2,minmax(0,1fr))' }, gap: 1.5 }}>
                <Box>
                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2, boxShadow: 'none' }}>
                  <Stack spacing={1}>
                    <Typography variant="caption" sx={{ fontWeight: 700 }}>Cover</Typography>
                    <TextField size="small" label="Cover URL" value={data.cover_url} onChange={(event) => setData('cover_url', event.target.value)} error={Boolean(errors.cover_url)} helperText={errors.cover_url || 'URL or upload'} />
                    <Stack direction="row" justifyContent="space-between" alignItems="center">
                      <Typography variant="caption" color="text.secondary" noWrap sx={{ maxWidth: 180 }}>{coverFileName || 'No uploaded image'}</Typography>
                      <Button component="label" size="small" variant="outlined">Upload<input hidden type="file" accept="image/*" onChange={handleCoverChange} /></Button>
                    </Stack>
                    <Box sx={{ height: 110, borderRadius: 1, overflow: 'hidden', bgcolor: 'action.hover' }}>
                      {(data.cover_image || data.cover_url) && <Box component="img" src={data.cover_image ? URL.createObjectURL(data.cover_image) : data.cover_url} alt="Cover" sx={{ width: '100%', height: '100%', objectFit: 'cover' }} />}
                    </Box>
                  </Stack>
                </Paper>
                </Box>
                <Box>
                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2, boxShadow: 'none' }}>
                  <Stack spacing={1}>
                    <Typography variant="caption" sx={{ fontWeight: 700 }}>Web Cover (16:9)</Typography>
                    <TextField size="small" label="Web Cover URL" value={data.web_cover} onChange={(event) => setData('web_cover', event.target.value)} error={Boolean(errors.web_cover)} helperText={errors.web_cover || 'URL or upload + crop'} />
                    <Stack direction="row" justifyContent="space-between" alignItems="center">
                      <Typography variant="caption" color="text.secondary" noWrap sx={{ maxWidth: 180 }}>{webCoverFileName || 'No uploaded image'}</Typography>
                      <Button component="label" size="small" variant="outlined">Upload & Crop<input hidden type="file" accept="image/*" onChange={handleWebCoverChange} /></Button>
                    </Stack>
                    <Box sx={{ borderRadius: 1, overflow: 'hidden', bgcolor: 'action.hover', aspectRatio: '16 / 9' }}>
                      {(data.web_cover_image || data.web_cover) && <Box component="img" src={data.web_cover_image ? URL.createObjectURL(data.web_cover_image) : data.web_cover} alt="Web Cover" sx={{ width: '100%', height: '100%', objectFit: 'cover' }} />}
                    </Box>
                  </Stack>
                </Paper>
                </Box>
              </Box>
              </>
              )}

              {activeStep === 2 && (
                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2, boxShadow: 'none' }}>
                  <Stack spacing={1}>
                    <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>Publish Review</Typography>
                    <Divider />
                    <Stack direction="row" spacing={0.5} useFlexGap flexWrap="wrap">
                      <Chip size="small" label={`Teacher: ${teachers.find((t) => String(t.id) === String(data.teacher_id))?.name || '-'}`} />
                      <Chip size="small" label={`Major: ${data.major || '-'}`} />
                      <Chip size="small" label={`VIP: ${data.is_vip ? 'Yes' : 'No'}`} />
                      <Chip size="small" label={`Active: ${data.active ? 'Yes' : 'No'}`} />
                    </Stack>
                    <Typography variant="body2"><strong>Title:</strong> {data.title || '-'}</Typography>
                    <Typography variant="body2"><strong>Certificate:</strong> {data.certificate_title || '-'} ({data.certificate_code || '-'})</Typography>
                    <Typography variant="body2"><strong>Description:</strong> {data.description || '-'}</Typography>
                  </Stack>
                </Paper>
              )}

              <Stack direction="row" justifyContent="space-between">
                <Button size="small" variant="outlined" onClick={handleBack} disabled={activeStep === 0 || processing}>
                  Back
                </Button>
                {activeStep < STEPS.length - 1 ? (
                  <Button size="small" variant="contained" onClick={handleNext} disabled={processing}>
                    Next
                  </Button>
                ) : (
                  <Button size="small" type="submit" variant="contained" startIcon={<AddIcon />} disabled={processing}>
                    Create Course
                  </Button>
                )}
              </Stack>
            </Stack>
          </Box>
        </Paper>
      </Stack>

      <ImageCropper
        open={webCoverCropperOpen}
        image={webCoverTempImage}
        onCropComplete={handleWebCoverCropComplete}
        onCancel={() => {
          setWebCoverCropperOpen(false);
          setWebCoverTempImage(null);
        }}
        aspect={16 / 9}
        title="Crop Web Cover (16:9)"
      />
      </Box>
  );
}

CourseCreate.layout = (page) => <AdminLayout children={page} />;
