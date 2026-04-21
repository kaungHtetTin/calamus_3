import React, { useRef, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import { Alert, Box, Button, Paper, Stack, Typography } from '@mui/material';
import html2canvas from 'html2canvas';

const certificateFont = '"Rosario", "Poppins", sans-serif';

function formatIssuedDate(dateStr) {
  const d = new Date(dateStr);
  const month = d.toLocaleDateString('en-US', { month: 'short' });
  const year = d.getFullYear();
  let day = d.getDate();
  if (day % 10 === 1 && day !== 11) day += 'st';
  else if (day % 10 === 2 && day !== 12) day += 'nd';
  else if (day % 10 === 3 && day !== 13) day += 'rd';
  else day += 'th';
  return `${month} ${day}, ${year}`;
}

export default function Certificate({ error = null, certificateData = null, courseId = null, userId = null }) {
  const { admin_app_url } = usePage().props;
  const [bgImageError, setBgImageError] = useState(false);
  const [sealImageError, setSealImageError] = useState(false);
  const captureRef = useRef(null);
  const [isExporting, setIsExporting] = useState(false);

  const toProxyUrl = (rawUrl) => {
    const value = String(rawUrl || '').trim();
    if (!value) return '';
    try {
      const resolved = new URL(value, window.location.origin);
      if (resolved.origin === window.location.origin) {
        return resolved.toString();
      }
      return `${admin_app_url}/certificate/image-proxy?url=${encodeURIComponent(resolved.toString())}`;
    } catch (e) {
      return value;
    }
  };

  const certificateBg = certificateData?.certificate_bg || "https://www.calamuseducation.com/uploads/icons/certificate/certificate_background.png";
  const qrUrl = certificateData?.url
    ? `https://api.qrserver.com/v1/create-qr-code/?size=90x90&data=${encodeURIComponent(certificateData.url)}`
    : '';
  const certificateSealRaw = certificateData?.seal
    ? (String(certificateData.seal).startsWith('http') ? certificateData.seal : `${window.location.origin}/${String(certificateData.seal).replace(/^\/+/, '')}`)
    : '';
  const certificateBgUrl = toProxyUrl(certificateBg);
  const certificateSeal = toProxyUrl(certificateSealRaw);

  const downloadJpg = async () => {
    if (!certificateData || error || !captureRef.current || isExporting) return;
    try {
      setIsExporting(true);
      const canvas = await html2canvas(captureRef.current, {
        backgroundColor: '#ffffff',
        scale: 3,
        useCORS: true,
      });
      const dataUrl = canvas.toDataURL('image/jpeg', 0.95);
      const link = document.createElement('a');
      const safeRef = String(certificateData?.ref || `course-${courseId || 'certificate'}-user-${userId || 'unknown'}`)
        .replace(/[^a-zA-Z0-9-_]/g, '_');
      link.href = dataUrl;
      link.download = `${safeRef}.jpg`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    } finally {
      setIsExporting(false);
    }
  };

  return (
    <AdminLayout>
      <Head title={certificateData?.ref ? `Certificate ${certificateData.ref}` : 'Certificate'} />
      <Stack spacing={1.5}>
        <Paper variant="outlined" sx={{ p: 1.5, borderRadius: 2 }}>
          <Stack direction={{ xs: 'column', md: 'row' }} spacing={1} justifyContent="space-between" alignItems={{ xs: 'flex-start', md: 'center' }}>
            <Box>
              <Typography variant="h6" sx={{ fontWeight: 700 }}>Certificate Generator</Typography>
              <Typography variant="body2" color="text.secondary">
                Course ID: {courseId || '-'} · User ID: {userId || '-'}
              </Typography>
            </Box>
            <Button component={Link} href={`${admin_app_url}/courses/${courseId || ''}/edit`} variant="outlined" size="small">
              Back to Course
            </Button>
          </Stack>
        </Paper>

        {Boolean(error) && <Alert severity="error">{error}</Alert>}

        {certificateData && !error && (
          <>
            <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
              <Box sx={{ overflow: 'auto', width: '100%' }}>
                <Box
                  ref={captureRef}
                  sx={{
                    position: 'relative',
                    width: 650,
                    height: 460,
                    margin: '0 auto',
                    overflow: 'visible',
                  }}
                >
                  {!bgImageError ? (
                    <Box
                      component="img"
                      src={certificateBgUrl}
                      alt=""
                      onError={() => setBgImageError(true)}
                      sx={{ width: '100%', height: '100%', objectFit: 'contain', display: 'block' }}
                    />
                  ) : (
                    <Box sx={{ width: '100%', height: '100%', bgcolor: '#f5f0e6' }} />
                  )}

                  <Typography sx={{ position: 'absolute', top: 70, width: '100%', textAlign: 'center', fontFamily: certificateFont, fontWeight: 700, fontSize: 30, letterSpacing: 5 }}>
                    CERTIFICATE OF COMPLETION
                  </Typography>
                  <Typography sx={{ position: 'absolute', top: 125, width: '100%', textAlign: 'center', fontFamily: certificateFont }}>
                    This is to certify that
                  </Typography>
                  <Typography sx={{ position: 'absolute', top: 160, width: '100%', textAlign: 'center', fontFamily: certificateFont, fontWeight: 700, fontSize: 30 }}>
                    {certificateData.name}
                  </Typography>
                  <Box sx={{ position: 'absolute', top: 200, left: 75, width: 500, height: 2, bgcolor: 'black' }} />
                  <Typography sx={{ position: 'absolute', top: 203, width: '100%', textAlign: 'center', fontFamily: certificateFont }}>
                    has completed the
                  </Typography>
                  <Typography sx={{ position: 'absolute', top: 231, width: '100%', textAlign: 'center', fontFamily: certificateFont, fontWeight: 700, fontSize: 22 }}>
                    {certificateData.course}
                  </Typography>
                  <Typography sx={{ position: 'absolute', top: 263, width: '100%', textAlign: 'center', fontFamily: certificateFont }}>
                    on the {certificateData.platform} platform by Calamus Education
                  </Typography>

                  {!sealImageError && certificateSeal && (
                    <Box
                      component="img"
                      src={certificateSeal}
                      alt=""
                      onError={() => setSealImageError(true)}
                      sx={{ position: 'absolute', bottom: 45, right: 60, width: 110, height: 110 }}
                    />
                  )}

                  <Box sx={{ position: 'absolute', bottom: 36, right: 40, width: 170, textAlign: 'center' }}>
                    <Typography sx={{ fontFamily: certificateFont, fontWeight: 700, fontSize: 13 }}>
                      Issued on {formatIssuedDate(certificateData.date)}
                    </Typography>
                  </Box>

                  <Box sx={{ position: 'absolute', bottom: 95, left: 38, fontSize: 12, textAlign: 'left', fontFamily: certificateFont }}>
                    <Typography sx={{ fontWeight: 700, fontSize: 12 }}>
                      Certificate ID : <span style={{ fontFamily: 'monospace' }}>{certificateData.ref}</span>
                    </Typography>
                    <Typography sx={{ fontSize: 12 }}>
                      Authorized by <strong>Calamus Education</strong>
                    </Typography>
                    <Typography sx={{ fontSize: 12 }}>
                      <strong>Sca</strong>n the <strong>QR</strong> code <strong>bel</strong>ow to <strong>ver</strong>ify this <strong>cer</strong>tificate and <strong>vie</strong>w course <strong>con</strong>tent.
                    </Typography>
                  </Box>

                  <Box sx={{ position: 'absolute', bottom: 37, left: 35, width: 55, height: 55, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                    {qrUrl && <Box component="img" src={qrUrl} alt="QR" sx={{ width: 45, height: 45 }} />}
                  </Box>
                </Box>
              </Box>
            </Paper>

            <Stack direction={{ xs: 'column', sm: 'row' }} spacing={1} justifyContent="center">
              <Button variant="contained" sx={{ textTransform: 'none' }} onClick={downloadJpg} disabled={isExporting}>
                {isExporting ? 'Exporting...' : 'Download JPG'}
              </Button>
              {certificateData.url ? (
                <Button
                  component={Link}
                  href={certificateData.url}
                  target="_blank"
                  rel="noreferrer"
                  variant="outlined"
                  sx={{ textTransform: 'none' }}
                >
                  Open Verification Link
                </Button>
              ) : null}
            </Stack>
          </>
        )}
      </Stack>
    </AdminLayout>
  );
}
