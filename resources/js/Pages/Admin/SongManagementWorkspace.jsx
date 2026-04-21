import React, { useEffect, useMemo, useState } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import ImageCropper from '../../Components/Admin/ImageCropper';
import {
  Alert,
  Avatar,
  Box,
  Button,
  Card,
  CardContent,
  Chip,
  Autocomplete,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  Divider,
  Grid,
  IconButton,
  List,
  ListItem,
  ListItemButton,
  ListItemIcon,
  ListItemText,
  MenuItem,
  Menu,
  Paper,
  Snackbar,
  Stack,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  TextField,
  Typography,
  useMediaQuery,
  useTheme,
} from '@mui/material';
import {
  Add as AddIcon,
  Audiotrack as AudiotrackIcon,
  Delete as DeleteIcon,
  Edit as EditIcon,
  Clear as ClearIcon,
  Description as DescriptionIcon,
  Language as LanguageIcon,
  MusicNote as MusicNoteIcon,
  RequestPage as RequestPageIcon,
  Person as PersonIcon,
  MoreVert as MoreVertIcon,
  Dashboard as DashboardIcon,
} from '@mui/icons-material';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip as ChartTooltip,
  ResponsiveContainer,
} from 'recharts';

const tabItems = [
  { key: 'overview', label: 'Overview', icon: <DashboardIcon fontSize="small" /> },
  { key: 'artist', label: 'Artist', icon: <PersonIcon fontSize="small" /> },
  { key: 'songs', label: 'Songs', icon: <MusicNoteIcon fontSize="small" /> },
  { key: 'requested-songs', label: 'Requested Songs', icon: <RequestPageIcon fontSize="small" /> },
];

const defaultArtistForm = {
  name: '',
  image_file: null,
};

const defaultSongForm = {
  title: '',
  artist_id: '',
  audio_file: null,
  cover_file: null,
  lyric_file: null,
};

export default function SongManagementWorkspace({
  languages = [],
  selectedMajor = '',
  selectedLanguage = null,
  tab = 'overview',
  overview = null,
  artists = [],
  artistOptions = [],
  songs = [],
  requestedSongs = [],
}) {
  const { admin_app_url, flash, errors: pageErrors } = usePage().props;
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('md'));
  const appBaseUrl = admin_app_url.replace(/\/admin\/?$/, '');

  const major = selectedMajor || (languages[0]?.code ?? '');
  const activeTab = tabItems.some((item) => item.key === tab) ? tab : 'overview';

  const buildImageUrl = (path) => {
    if (!path) {
      return '';
    }
    if (path.startsWith('http://') || path.startsWith('https://')) {
      return path;
    }
    const normalizedPath = path.startsWith('/') ? path : `/${path}`;
    return `${appBaseUrl}${normalizedPath}`;
  };

  const openWorkspace = (nextMajor, nextTab) => {
    const majorParam = encodeURIComponent(nextMajor);
    const tabParam = nextTab ? `&tab=${encodeURIComponent(nextTab)}` : '';
    window.location.href = `${admin_app_url}/songs/workspace?major=${majorParam}${tabParam}`;
  };

  const [artistDialogOpen, setArtistDialogOpen] = useState(false);
  const [editingArtist, setEditingArtist] = useState(null);
  const {
    data: artistData,
    setData: setArtistData,
    post: postArtist,
    processing: artistProcessing,
    errors: artistErrors,
    reset: resetArtist,
    clearErrors: clearArtistErrors,
  } = useForm(defaultArtistForm);

  const activeTabMeta = useMemo(() => tabItems.find((item) => item.key === activeTab), [activeTab]);
  const overviewCounts = overview?.counts || { artists: 0, songs: 0, requested_songs: 0 };
  const topArtistsChart = useMemo(() => {
    return (overview?.top_artists || []).map((row) => ({
      name: row.name,
      value: Number(row.score || 0),
      songs: Number(row.songs_count || 0),
    }));
  }, [overview]);
  const topSongsChart = useMemo(() => {
    return (overview?.top_songs || []).map((row) => ({
      name: row.title,
      value: Number(row.score || 0),
    }));
  }, [overview]);
  const [artistImagePreview, setArtistImagePreview] = useState('');
  const [artistImageName, setArtistImageName] = useState('');
  const [artistCropperOpen, setArtistCropperOpen] = useState(false);
  const [artistCropTempImage, setArtistCropTempImage] = useState(null);

  const [songDialogOpen, setSongDialogOpen] = useState(false);
  const [editingSong, setEditingSong] = useState(null);
  const [audioPlayerOpen, setAudioPlayerOpen] = useState(false);
  const [audioPlayerUrl, setAudioPlayerUrl] = useState('');
  const [audioPlayerTitle, setAudioPlayerTitle] = useState('');
  const [artistMenuAnchorEl, setArtistMenuAnchorEl] = useState(null);
  const [artistMenuRow, setArtistMenuRow] = useState(null);
  const [songMenuAnchorEl, setSongMenuAnchorEl] = useState(null);
  const [songMenuRow, setSongMenuRow] = useState(null);

  const [requestedArtistId, setRequestedArtistId] = useState('all');
  const [requestedSearch, setRequestedSearch] = useState('');
  const filteredRequestedSongs = useMemo(() => {
    const keyword = String(requestedSearch || '').trim().toLowerCase();
    const artistFilterId = String(requestedArtistId || 'all');
    return (requestedSongs || [])
      .filter((row) => {
        if (artistFilterId !== 'all' && String(row.artist_id) !== artistFilterId) {
          return false;
        }
        if (!keyword) {
          return true;
        }
        const name = String(row.name || '').toLowerCase();
        const artistName = String(row.artist_name || '').toLowerCase();
        return name.includes(keyword) || artistName.includes(keyword);
      })
      .sort((a, b) => {
        const voteDiff = Number(b.vote || 0) - Number(a.vote || 0);
        if (voteDiff !== 0) return voteDiff;
        return Number(b.id || 0) - Number(a.id || 0);
      });
  }, [requestedSongs, requestedArtistId, requestedSearch]);
  const {
    data: songData,
    setData: setSongData,
    post: postSong,
    processing: songProcessing,
    errors: songErrors,
    reset: resetSong,
    clearErrors: clearSongErrors,
  } = useForm(defaultSongForm);
  const [songAudioName, setSongAudioName] = useState('');
  const [songImageName, setSongImageName] = useState('');
  const [songLyricName, setSongLyricName] = useState('');
  const [songCoverPreview, setSongCoverPreview] = useState('');
  const [songCoverCropperOpen, setSongCoverCropperOpen] = useState(false);
  const [songCoverTempImage, setSongCoverTempImage] = useState(null);

  useEffect(() => {
    return () => {
      if (artistImagePreview && artistImagePreview.startsWith('blob:')) {
        URL.revokeObjectURL(artistImagePreview);
      }
    };
  }, [artistImagePreview]);

  useEffect(() => {
    return () => {
      if (artistCropTempImage && artistCropTempImage.startsWith('blob:')) {
        URL.revokeObjectURL(artistCropTempImage);
      }
    };
  }, [artistCropTempImage]);

  const openCreateArtist = () => {
    setEditingArtist(null);
    clearArtistErrors();
    resetArtist();
    setArtistData('name', '');
    setArtistData('image_file', null);
    setArtistImagePreview('');
    setArtistImageName('');
    setArtistCropTempImage(null);
    setArtistCropperOpen(false);
    setArtistDialogOpen(true);
  };

  const openEditArtist = (artist) => {
    setEditingArtist(artist);
    clearArtistErrors();
    setArtistData({
      name: artist?.name || '',
      image_file: null,
    });
    setArtistImagePreview(artist?.image_url || '');
    setArtistImageName('');
    setArtistCropTempImage(null);
    setArtistCropperOpen(false);
    setArtistDialogOpen(true);
  };

  const handleArtistImageChange = (event) => {
    const file = event.target.files?.[0] || null;
    if (!file) {
      setArtistImageName('');
      return;
    }
    setArtistImageName(file.name);
    const nextTemp = URL.createObjectURL(file);
    setArtistCropTempImage((prev) => {
      if (prev && prev.startsWith('blob:')) {
        URL.revokeObjectURL(prev);
      }
      return nextTemp;
    });
    setArtistCropperOpen(true);
  };

  const submitArtist = (event) => {
    event.preventDefault();
    if (!major) {
      return;
    }

    const query = `?major=${encodeURIComponent(major)}&tab=artist`;
    if (editingArtist) {
      postArtist(`${admin_app_url}/songs/artists/${editingArtist.id}${query}`, {
        data: { ...artistData, _method: 'patch' },
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
          setArtistDialogOpen(false);
          setEditingArtist(null);
        },
      });
      return;
    }

    postArtist(`${admin_app_url}/songs/artists${query}`, {
      data: artistData,
      preserveScroll: true,
      forceFormData: true,
      onSuccess: () => {
        setArtistDialogOpen(false);
      },
    });
  };

  const removeArtist = (artist) => {
    if (!artist) {
      return;
    }
    if (!confirm(`Delete artist "${artist.name}"?`)) {
      return;
    }
    router.delete(`${admin_app_url}/songs/artists/${artist.id}?major=${encodeURIComponent(major)}&tab=artist`, { preserveScroll: true });
  };

  const openArtistMenu = (event, row) => {
    setArtistMenuAnchorEl(event.currentTarget);
    setArtistMenuRow(row);
  };

  const closeArtistMenu = () => {
    setArtistMenuAnchorEl(null);
    setArtistMenuRow(null);
  };

  const openCreateSong = () => {
    setEditingSong(null);
    clearSongErrors();
    resetSong();
    const defaultArtistId = artistOptions?.[0]?.id ? String(artistOptions[0].id) : '';
    setSongData('title', '');
    setSongData('artist_id', defaultArtistId);
    setSongData('audio_file', null);
    setSongData('cover_file', null);
    setSongData('lyric_file', null);
    setSongAudioName('');
    setSongImageName('');
    setSongLyricName('');
    setSongCoverPreview('');
    setSongCoverTempImage(null);
    setSongCoverCropperOpen(false);
    setSongDialogOpen(true);
  };

  const openEditSong = (song) => {
    setEditingSong(song);
    clearSongErrors();
    resetSong();
    setSongData('title', song?.title || '');
    setSongData('artist_id', song?.artist_id ? String(song.artist_id) : '');
    setSongData('audio_file', null);
    setSongData('cover_file', null);
    setSongData('lyric_file', null);
    setSongAudioName('');
    setSongImageName('');
    setSongLyricName('');
    setSongCoverPreview(song?.image_url || song?.thumbnail_url || '');
    setSongCoverTempImage(null);
    setSongCoverCropperOpen(false);
    setSongDialogOpen(true);
  };

  const submitSong = (event) => {
    event.preventDefault();
    if (!major) {
      return;
    }
    const query = `?major=${encodeURIComponent(major)}&tab=songs`;
    if (editingSong) {
      postSong(`${admin_app_url}/songs/songs/${editingSong.id}${query}`, {
        data: { ...songData, _method: 'patch' },
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
          setSongDialogOpen(false);
          setEditingSong(null);
        },
      });
      return;
    }
    postSong(`${admin_app_url}/songs/songs${query}`, {
      data: songData,
      preserveScroll: true,
      forceFormData: true,
      onSuccess: () => {
        setSongDialogOpen(false);
      },
    });
  };

  const removeSong = (song) => {
    if (!song) {
      return;
    }
    if (!confirm(`Delete song "${song.title}"?`)) {
      return;
    }
    router.delete(`${admin_app_url}/songs/songs/${song.id}?major=${encodeURIComponent(major)}&tab=songs`, { preserveScroll: true });
  };

  const openSongMenu = (event, row) => {
    setSongMenuAnchorEl(event.currentTarget);
    setSongMenuRow(row);
  };

  const closeSongMenu = () => {
    setSongMenuAnchorEl(null);
    setSongMenuRow(null);
  };

  const handleSongFileChange = (key, setter) => (event) => {
    const file = event.target.files?.[0] || null;
    setSongData(key, file);
    setter(file ? file.name : '');
  };

  const handleSongCoverChange = (event) => {
    const file = event.target.files?.[0] || null;
    if (!file) {
      setSongImageName('');
      return;
    }
    setSongImageName(file.name);
    const tmp = URL.createObjectURL(file);
    setSongCoverTempImage((prev) => {
      if (prev && prev.startsWith('blob:')) {
        URL.revokeObjectURL(prev);
      }
      return tmp;
    });
    setSongCoverCropperOpen(true);
  };

  const openAudioPlayer = (song) => {
    if (!song?.audio_url) {
      return;
    }
    setAudioPlayerTitle(song.title || '');
    setAudioPlayerUrl(song.audio_url);
    setAudioPlayerOpen(true);
  };

  const openLyricEditor = (song) => {
    if (!song) {
      return;
    }
    router.visit(`${admin_app_url}/songs/songs/${song.id}/lyric?major=${encodeURIComponent(major)}`);
  };

  return (
    <Box>
      <Head title="Song Management Workspace" />
      <Stack spacing={2.5}>
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              Song Management Workspace
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Channel: {major || '-'}
            </Typography>
          </Box>
        </Box>

        <Box sx={{ display: { xs: 'block', md: 'flex' }, gap: 2, alignItems: 'flex-start' }}>
          <Stack spacing={1.5} sx={{ flex: 1, minWidth: 0 }}>
            {activeTab === 'artist' ? (
              <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                <Stack direction="row" spacing={1} alignItems="center" justifyContent="space-between" sx={{ mb: 1 }}>
                  <Stack direction="row" spacing={1} alignItems="center">
                    {activeTabMeta?.icon}
                    <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                      Artist
                    </Typography>
                  </Stack>
                  <Button size="small" variant="contained" startIcon={<AddIcon />} onClick={openCreateArtist}>
                    Add Artist
                  </Button>
                </Stack>
                {Boolean(pageErrors?.artist) && (
                  <Alert severity="error" sx={{ mb: 1.25 }}>
                    {pageErrors.artist}
                  </Alert>
                )}
                <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
                  <Table size="small">
                    <TableHead>
                      <TableRow>
                        <TableCell sx={{ fontWeight: 700 }}>Artist</TableCell>
                        <TableCell sx={{ fontWeight: 700 }}>Songs</TableCell>
                        <TableCell align="right" sx={{ fontWeight: 700, width: 120 }}>Actions</TableCell>
                      </TableRow>
                    </TableHead>
                    <TableBody>
                      {artists.map((a) => (
                        <TableRow key={a.id} hover>
                          <TableCell>
                            <Stack direction="row" spacing={1} alignItems="center">
                              <Avatar
                                src={a.image_url || ''}
                                sx={{ width: 32, height: 32, bgcolor: 'action.selected' }}
                              >
                                <PersonIcon fontSize="small" />
                              </Avatar>
                              <Box sx={{ minWidth: 0 }}>
                                <Typography variant="body2" sx={{ fontWeight: 700 }} noWrap>
                                  {a.name}
                                </Typography>
                                <Typography variant="caption" color="text.secondary" noWrap sx={{ display: 'block' }}>
                                  ID {a.id}
                                </Typography>
                              </Box>
                            </Stack>
                          </TableCell>
                          <TableCell>
                            <Chip size="small" label={Number(a.song_count || 0)} />
                          </TableCell>
                          <TableCell align="right" sx={{ whiteSpace: 'nowrap' }}>
                            <IconButton size="small" onClick={(e) => openArtistMenu(e, a)} title="Actions" sx={{ p: 0.5 }}>
                              <MoreVertIcon fontSize="small" />
                            </IconButton>
                          </TableCell>
                        </TableRow>
                      ))}
                      {artists.length === 0 && (
                        <TableRow>
                          <TableCell colSpan={3}>
                            <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                              No artists for this channel.
                            </Typography>
                          </TableCell>
                        </TableRow>
                      )}
                    </TableBody>
                  </Table>
                </TableContainer>
              </Paper>
            ) : activeTab === 'overview' ? (
              <Stack spacing={1.5}>
                <Grid container spacing={1.5}>
                  <Grid item xs={12} sm={4}>
                    <Card variant="outlined" sx={{ borderRadius: 2, boxShadow: 'none' }}>
                      <CardContent sx={{ p: 1.5, '&:last-child': { pb: 1.5 } }}>
                        <Typography variant="body2" color="text.secondary">
                          Artists
                        </Typography>
                        <Typography variant="h5" sx={{ mt: 0.5, fontWeight: 700, lineHeight: 1.1 }}>
                          {overviewCounts.artists}
                        </Typography>
                        <Chip size="small" label="Current channel" color="info" sx={{ mt: 1 }} />
                      </CardContent>
                    </Card>
                  </Grid>
                  <Grid item xs={12} sm={4}>
                    <Card variant="outlined" sx={{ borderRadius: 2, boxShadow: 'none' }}>
                      <CardContent sx={{ p: 1.5, '&:last-child': { pb: 1.5 } }}>
                        <Typography variant="body2" color="text.secondary">
                          Songs
                        </Typography>
                        <Typography variant="h5" sx={{ mt: 0.5, fontWeight: 700, lineHeight: 1.1 }}>
                          {overviewCounts.songs}
                        </Typography>
                        <Chip size="small" label="Uploaded" color="success" sx={{ mt: 1 }} />
                      </CardContent>
                    </Card>
                  </Grid>
                  <Grid item xs={12} sm={4}>
                    <Card variant="outlined" sx={{ borderRadius: 2, boxShadow: 'none' }}>
                      <CardContent sx={{ p: 1.5, '&:last-child': { pb: 1.5 } }}>
                        <Typography variant="body2" color="text.secondary">
                          Requested Songs
                        </Typography>
                        <Typography variant="h5" sx={{ mt: 0.5, fontWeight: 700, lineHeight: 1.1 }}>
                          {overviewCounts.requested_songs}
                        </Typography>
                        <Chip size="small" label="Community" color="warning" sx={{ mt: 1 }} />
                      </CardContent>
                    </Card>
                  </Grid>
                </Grid>

                <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', lg: 'repeat(2, minmax(0, 1fr))' }, gap: 1.5 }}>
                  <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                    <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1 }}>
                      <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
                        Popular Artists
                      </Typography>
                      <Chip size="small" label="Score = likes + downloads" variant="outlined" />
                    </Stack>
                    <Divider sx={{ mb: 1.5 }} />
                    <Box sx={{ height: 280 }}>
                      <ResponsiveContainer width="100%" height="100%">
                        <BarChart data={topArtistsChart} margin={{ left: 8, right: 12, top: 8, bottom: 8 }}>
                          <CartesianGrid strokeDasharray="3 3" vertical={false} stroke={theme.palette.divider} />
                          <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: theme.palette.text.secondary }} />
                          <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: theme.palette.text.secondary }} />
                          <ChartTooltip
                            cursor={{ fill: 'transparent' }}
                            contentStyle={{
                              borderRadius: 12,
                              border: 'none',
                              boxShadow: theme.shadows[3],
                              backgroundColor: theme.palette.background.paper,
                            }}
                          />
                          <Bar dataKey="value" fill={theme.palette.primary.main} radius={[4, 4, 0, 0]} barSize={26} />
                        </BarChart>
                      </ResponsiveContainer>
                    </Box>
                  </Paper>

                  <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                    <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1 }}>
                      <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
                        Popular Songs
                      </Typography>
                      <Chip size="small" label="Score = likes + downloads" variant="outlined" />
                    </Stack>
                    <Divider sx={{ mb: 1.5 }} />
                    <Box sx={{ height: 280 }}>
                      <ResponsiveContainer width="100%" height="100%">
                        <BarChart data={topSongsChart} layout="vertical" margin={{ left: 12, right: 12, top: 8, bottom: 8 }}>
                          <CartesianGrid strokeDasharray="3 3" horizontal={false} stroke={theme.palette.divider} />
                          <XAxis type="number" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: theme.palette.text.secondary }} />
                          <YAxis
                            type="category"
                            dataKey="name"
                            width={120}
                            axisLine={false}
                            tickLine={false}
                            tick={{ fontSize: 12, fill: theme.palette.text.secondary }}
                          />
                          <ChartTooltip
                            cursor={{ fill: 'transparent' }}
                            contentStyle={{
                              borderRadius: 12,
                              border: 'none',
                              boxShadow: theme.shadows[3],
                              backgroundColor: theme.palette.background.paper,
                            }}
                          />
                          <Bar dataKey="value" fill={theme.palette.info.main} radius={[0, 4, 4, 0]} barSize={16} />
                        </BarChart>
                      </ResponsiveContainer>
                    </Box>
                  </Paper>
                </Box>

                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                  <Stack direction="row" justifyContent="space-between" alignItems="center" sx={{ mb: 1 }}>
                    <Typography variant="subtitle1" sx={{ fontWeight: 600 }}>
                      Most Voted Requested Songs
                    </Typography>
                    <Chip size="small" label="Top 8" variant="outlined" />
                  </Stack>
                  <Divider sx={{ mb: 1.5 }} />
                  <TableContainer>
                    <Table size="small">
                      <TableHead>
                        <TableRow>
                          <TableCell sx={{ fontWeight: 700 }}>Song</TableCell>
                          <TableCell sx={{ fontWeight: 700 }}>Artist</TableCell>
                          <TableCell sx={{ fontWeight: 700 }}>Votes</TableCell>
                          <TableCell sx={{ fontWeight: 700 }}>Status</TableCell>
                        </TableRow>
                      </TableHead>
                      <TableBody>
                        {(overview?.top_requested || []).map((r) => (
                          <TableRow key={`overview-request-${r.id}`} hover sx={r.is_uploaded ? { bgcolor: 'rgba(211, 47, 47, 0.06)' } : undefined}>
                            <TableCell>
                              <Typography variant="body2" sx={{ fontWeight: 700 }}>
                                {r.name}
                              </Typography>
                              <Typography variant="caption" color="text.secondary">
                                ID {r.id}
                              </Typography>
                            </TableCell>
                            <TableCell>
                              <Typography variant="body2">{r.artist_name || '-'}</Typography>
                            </TableCell>
                            <TableCell>
                              <Chip size="small" label={Number(r.vote || 0)} />
                            </TableCell>
                            <TableCell>
                              {r.is_uploaded ? (
                                <Chip size="small" color="error" label="Already uploaded" />
                              ) : (
                                <Chip size="small" variant="outlined" label="Not uploaded" />
                              )}
                            </TableCell>
                          </TableRow>
                        ))}
                        {(!overview?.top_requested || overview.top_requested.length === 0) && (
                          <TableRow>
                            <TableCell colSpan={4}>
                              <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                                No requested songs found.
                              </Typography>
                            </TableCell>
                          </TableRow>
                        )}
                      </TableBody>
                    </Table>
                  </TableContainer>
                </Paper>
              </Stack>
            ) : activeTab === 'songs' ? (
              <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                <Stack direction="row" spacing={1} alignItems="center" justifyContent="space-between" sx={{ mb: 1 }}>
                  <Stack direction="row" spacing={1} alignItems="center">
                    {activeTabMeta?.icon}
                    <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                      Songs
                    </Typography>
                  </Stack>
                  <Button size="small" variant="contained" startIcon={<AddIcon />} onClick={openCreateSong}>
                    Add Song
                  </Button>
                </Stack>
                {Boolean(pageErrors?.song) && (
                  <Alert severity="error" sx={{ mb: 1.25 }}>
                    {pageErrors.song}
                  </Alert>
                )}
                <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
                  <Table size="small">
                    <TableHead>
                      <TableRow>
                        <TableCell sx={{ fontWeight: 700 }}>Title</TableCell>
                        <TableCell sx={{ fontWeight: 700 }}>Artist</TableCell>
                        <TableCell sx={{ fontWeight: 700 }}>Assets</TableCell>
                        <TableCell align="right" sx={{ fontWeight: 700 }}>Actions</TableCell>
                      </TableRow>
                    </TableHead>
                    <TableBody>
                      {songs.map((s) => (
                        <TableRow key={s.id} hover>
                          <TableCell>
                            <Stack direction="row" spacing={1} alignItems="center">
                              <Avatar
                                src={s.image_url || s.thumbnail_url || ''}
                                sx={{ width: 32, height: 32, bgcolor: 'action.selected' }}
                              >
                                <MusicNoteIcon fontSize="small" />
                              </Avatar>
                              <Box sx={{ minWidth: 0 }}>
                                <Typography variant="body2" sx={{ fontWeight: 700 }} noWrap>
                                  {s.title}
                                </Typography>
                                <Typography variant="caption" color="text.secondary" noWrap sx={{ display: 'block' }}>
                                  ID {s.id}
                                </Typography>
                              </Box>
                            </Stack>
                          </TableCell>
                          <TableCell>
                            <Typography variant="body2">{s.artist_name || '-'}</Typography>
                          </TableCell>
                          <TableCell>
                            <Stack direction="row" spacing={0.25} alignItems="center">
                              <IconButton size="small" onClick={() => openAudioPlayer(s)} disabled={!s.audio_url} title="Play Audio">
                                <AudiotrackIcon fontSize="small" />
                              </IconButton>
                              <IconButton size="small" onClick={() => openLyricEditor(s)} title="Edit Lyrics">
                                <DescriptionIcon fontSize="small" />
                              </IconButton>
                            </Stack>
                          </TableCell>
                          <TableCell align="right" sx={{ whiteSpace: 'nowrap' }}>
                            <IconButton size="small" onClick={(e) => openSongMenu(e, s)} title="Actions" sx={{ p: 0.5 }}>
                              <MoreVertIcon fontSize="small" />
                            </IconButton>
                          </TableCell>
                        </TableRow>
                      ))}
                      {songs.length === 0 && (
                        <TableRow>
                          <TableCell colSpan={4}>
                            <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                              No songs for this channel.
                            </Typography>
                          </TableCell>
                        </TableRow>
                      )}
                    </TableBody>
                  </Table>
                </TableContainer>
              </Paper>
            ) : activeTab === 'requested-songs' ? (
              <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                <Stack direction="row" spacing={1} alignItems="center" justifyContent="space-between" sx={{ mb: 1 }}>
                  <Stack direction="row" spacing={1} alignItems="center">
                    {activeTabMeta?.icon}
                    <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                      Requested Songs
                    </Typography>
                  </Stack>
                </Stack>

                <Paper variant="outlined" sx={{ p: 1, borderRadius: 1.25, mb: 1 }}>
                  <Stack direction={{ xs: 'column', sm: 'row' }} spacing={1}>
                    <TextField
                      size="small"
                      select
                      label="Artist"
                      value={requestedArtistId}
                      onChange={(e) => setRequestedArtistId(e.target.value)}
                      sx={{ minWidth: 220 }}
                    >
                      <MenuItem value="all">All</MenuItem>
                      {artistOptions.map((a) => (
                        <MenuItem key={`req-artist-${a.id}`} value={String(a.id)}>
                          {a.name}
                        </MenuItem>
                      ))}
                    </TextField>
                    <TextField
                      size="small"
                      label="Search song"
                      value={requestedSearch}
                      onChange={(e) => setRequestedSearch(e.target.value)}
                      fullWidth
                    />
                  </Stack>
                </Paper>

                <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2 }}>
                  <Table size="small">
                    <TableHead>
                      <TableRow>
                        <TableCell sx={{ fontWeight: 700 }}>Song</TableCell>
                        <TableCell sx={{ fontWeight: 700 }}>Artist</TableCell>
                        <TableCell sx={{ fontWeight: 700 }}>Votes</TableCell>
                        <TableCell sx={{ fontWeight: 700 }}>Status</TableCell>
                        <TableCell align="right" sx={{ fontWeight: 700, width: 70 }}>Delete</TableCell>
                      </TableRow>
                    </TableHead>
                    <TableBody>
                      {filteredRequestedSongs.map((r) => (
                        <TableRow key={r.id} hover sx={r.is_uploaded ? { bgcolor: 'rgba(211, 47, 47, 0.06)' } : undefined}>
                          <TableCell>
                            <Typography variant="body2" sx={{ fontWeight: 700 }}>
                              {r.name}
                            </Typography>
                            <Typography variant="caption" color="text.secondary">
                              ID {r.id}
                            </Typography>
                          </TableCell>
                          <TableCell>
                            <Typography variant="body2">{r.artist_name || '-'}</Typography>
                          </TableCell>
                          <TableCell>
                            <Chip size="small" label={Number(r.vote || 0)} />
                          </TableCell>
                          <TableCell>
                            {r.is_uploaded ? (
                              <Chip size="small" color="error" label="Already uploaded" />
                            ) : (
                              <Chip size="small" variant="outlined" label="Not uploaded" />
                            )}
                          </TableCell>
                          <TableCell align="right" sx={{ whiteSpace: 'nowrap' }}>
                            <IconButton
                              size="small"
                              color="error"
                              onClick={() => {
                                if (confirm(`Delete requested song "${r.name}"?`)) {
                                  router.delete(`${admin_app_url}/songs/requested-songs/${r.id}?major=${encodeURIComponent(major)}&tab=requested-songs`, { preserveScroll: true });
                                }
                              }}
                              title="Delete"
                            >
                              <DeleteIcon fontSize="small" />
                            </IconButton>
                          </TableCell>
                        </TableRow>
                      ))}
                      {filteredRequestedSongs.length === 0 && (
                        <TableRow>
                          <TableCell colSpan={5}>
                            <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                              No requested songs found.
                            </Typography>
                          </TableCell>
                        </TableRow>
                      )}
                    </TableBody>
                  </Table>
                </TableContainer>
              </Paper>
            ) : (
              <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                <Stack direction="row" spacing={1} alignItems="center" sx={{ mb: 0.75 }}>
                  {activeTabMeta?.icon}
                  <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                    {activeTabMeta?.label || 'Overview'}
                  </Typography>
                </Stack>
                <Typography variant="body2" color="text.secondary">
                  Placeholder content for {activeTabMeta?.label || activeTab}. This will be implemented next.
                </Typography>
              </Paper>
            )}
          </Stack>

          <Paper
            variant="outlined"
            sx={{
              borderRadius: 2,
              overflow: 'hidden',
              alignSelf: { xs: 'stretch', md: 'start' },
              position: { md: 'sticky' },
              top: { md: 72 },
              width: { xs: '100%', md: 320 },
            }}
          >
            <Box sx={{ p: 2, borderBottom: '1px solid', borderColor: 'divider' }}>
              <Stack direction="row" spacing={1.25} alignItems="center">
                <Avatar
                  src={buildImageUrl(selectedLanguage?.image_path)}
                  sx={{ width: 36, height: 36, bgcolor: selectedLanguage?.primary_color || 'action.selected' }}
                >
                  <LanguageIcon fontSize="small" />
                </Avatar>
                <Box sx={{ minWidth: 0 }}>
                  <Typography variant="subtitle2" sx={{ fontWeight: 700 }} noWrap>
                    {selectedLanguage?.display_name || selectedLanguage?.name || major || 'Channel'}
                  </Typography>
                  <Typography variant="caption" color="text.secondary" noWrap sx={{ display: 'block' }}>
                    {major || '-'}
                  </Typography>
                </Box>
              </Stack>
              <Divider sx={{ my: 1.25 }} />
              <TextField
                size="small"
                select
                fullWidth
                label="Channel / Major"
                value={major}
                onChange={(event) => openWorkspace(event.target.value, activeTab)}
              >
                {languages.map((l) => (
                  <MenuItem key={l.code} value={l.code}>
                    {l.display_name || l.name || l.code}
                  </MenuItem>
                ))}
              </TextField>
            </Box>
            <List dense sx={{ p: 1 }}>
              {tabItems.map((item) => (
                <ListItem key={item.key} disablePadding sx={{ mb: 0.25 }}>
                  <ListItemButton
                    selected={item.key === activeTab}
                    sx={{ borderRadius: 1 }}
                    onClick={() => openWorkspace(major, item.key)}
                  >
                    <ListItemIcon sx={{ minWidth: 34 }}>
                      {item.icon}
                    </ListItemIcon>
                    <ListItemText
                      primary={item.label}
                      primaryTypographyProps={{ fontSize: 13, fontWeight: item.key === activeTab ? 700 : 500, noWrap: true }}
                    />
                  </ListItemButton>
                </ListItem>
              ))}
            </List>
            {isMobile && <Divider />}
          </Paper>
        </Box>
      </Stack>

      <Dialog open={artistDialogOpen} onClose={() => setArtistDialogOpen(false)} maxWidth="sm" fullWidth>
        <DialogTitle>{editingArtist ? 'Edit Artist' : 'Add Artist'}</DialogTitle>
        <Box component="form" onSubmit={submitArtist}>
          <DialogContent dividers>
            <Stack spacing={1}>
              <TextField
                label="Name"
                value={artistData.name}
                onChange={(event) => setArtistData('name', event.target.value)}
                error={Boolean(artistErrors.name)}
                helperText={artistErrors.name}
                fullWidth
                size="small"
              />
              <TextField label="Channel / Major" value={major} InputProps={{ readOnly: true }} fullWidth size="small" />
              <Paper variant="outlined" sx={{ p: 1, borderRadius: 1.25 }}>
                <Stack direction={{ xs: 'column', sm: 'row' }} spacing={1} alignItems={{ xs: 'flex-start', sm: 'center' }} justifyContent="space-between">
                  <Stack direction="row" spacing={1} alignItems="center">
                    <Avatar
                      src={artistImagePreview}
                      sx={{ width: 34, height: 34, bgcolor: 'action.selected' }}
                    >
                      <PersonIcon fontSize="small" />
                    </Avatar>
                    <Box sx={{ minWidth: 0 }}>
                      <Typography variant="body2" sx={{ fontWeight: 700 }} noWrap>
                        Artist Image
                      </Typography>
                      <Typography variant="caption" color="text.secondary" noWrap sx={{ display: 'block' }}>
                        {artistImageName || (editingArtist?.image_url ? 'Current image' : 'No file selected')}
                      </Typography>
                    </Box>
                  </Stack>
                  <Button component="label" size="small" variant="outlined">
                    Upload & Crop
                    <input hidden type="file" accept="image/*" onChange={handleArtistImageChange} />
                  </Button>
                </Stack>
                {Boolean(artistErrors.image_file) && (
                  <Typography variant="caption" color="error.main" sx={{ mt: 0.75, display: 'block' }}>
                    {artistErrors.image_file}
                  </Typography>
                )}
              </Paper>
              {Boolean(artistErrors.artist) && (
                <Alert severity="error">{artistErrors.artist}</Alert>
              )}
            </Stack>
          </DialogContent>
          <DialogActions>
            <Button onClick={() => setArtistDialogOpen(false)} disabled={artistProcessing}>
              Cancel
            </Button>
            <Button type="submit" variant="contained" disabled={artistProcessing}>
              {editingArtist ? 'Update' : 'Create'}
            </Button>
          </DialogActions>
        </Box>
      </Dialog>

      <ImageCropper
        open={artistCropperOpen}
        image={artistCropTempImage}
        aspect={1 / 1}
        title="Crop Artist Image (1:1)"
        onCancel={() => {
          setArtistCropperOpen(false);
          setArtistCropTempImage(null);
        }}
        onCropComplete={(blob) => {
          if (!blob) {
            setArtistCropperOpen(false);
            setArtistCropTempImage(null);
            return;
          }
          const file = new File([blob], `artist_${Date.now()}.jpg`, { type: 'image/jpeg' });
          setArtistData('image_file', file);
          setArtistImageName(file.name);
          const previewUrl = URL.createObjectURL(file);
          setArtistImagePreview((prev) => {
            if (prev && prev.startsWith('blob:')) {
              URL.revokeObjectURL(prev);
            }
            return previewUrl;
          });
          setArtistCropperOpen(false);
          setArtistCropTempImage(null);
        }}
      />

      <ImageCropper
        open={songCoverCropperOpen}
        image={songCoverTempImage}
        aspect={3 / 4}
        title="Crop Song Cover (3:4)"
        onCancel={() => {
          setSongCoverCropperOpen(false);
          setSongCoverTempImage(null);
        }}
        onCropComplete={(blob) => {
          if (!blob) {
            setSongCoverCropperOpen(false);
            setSongCoverTempImage(null);
            return;
          }
          const file = new File([blob], `song_cover_${Date.now()}.jpg`, { type: 'image/jpeg' });
          setSongData('cover_file', file);
          setSongImageName(file.name);
          const previewUrl = URL.createObjectURL(file);
          setSongCoverPreview((prev) => {
            if (prev && prev.startsWith('blob:')) {
              URL.revokeObjectURL(prev);
            }
            return previewUrl;
          });
          setSongCoverCropperOpen(false);
          setSongCoverTempImage(null);
        }}
      />

      <Dialog open={songDialogOpen} onClose={() => setSongDialogOpen(false)} maxWidth="sm" fullWidth>
        <DialogTitle>{editingSong ? 'Edit Song' : 'Add Song'}</DialogTitle>
        <Box component="form" onSubmit={submitSong}>
          <DialogContent dividers>
            <Stack spacing={1}>
              <TextField
                label="Title"
                value={songData.title}
                onChange={(event) => setSongData('title', event.target.value)}
                error={Boolean(songErrors.title)}
                helperText={songErrors.title}
                fullWidth
                size="small"
              />
              <Autocomplete
                value={artistOptions.find((x) => String(x.id) === String(songData.artist_id)) || null}
                onChange={(_, value) => setSongData('artist_id', value ? String(value.id) : '')}
                options={artistOptions}
                getOptionLabel={(option) => option?.name || ''}
                renderInput={(params) => (
                  <TextField
                    {...params}
                    label="Artist"
                    error={Boolean(songErrors.artist_id)}
                    helperText={songErrors.artist_id}
                    fullWidth
                    size="small"
                  />
                )}
              />
              <TextField label="Channel / Major" value={major} InputProps={{ readOnly: true }} fullWidth size="small" />

              <Stack direction={{ xs: 'column', sm: 'row' }} spacing={1}>
                <Paper variant="outlined" sx={{ p: 1, borderRadius: 1.25, flex: 1 }}>
                  <Stack spacing={1} direction="row" alignItems="center" justifyContent="space-between">
                    <Stack spacing={0.5}>
                      <Button component="label" size="small" variant="outlined">
                        Upload Audio (.mp3)
                        <input hidden type="file" accept=".mp3,audio/mpeg" onChange={handleSongFileChange('audio_file', setSongAudioName)} />
                      </Button>
                      <Typography variant="caption" color="text.secondary">
                        {songAudioName || 'No audio file selected (max 50MB)'}
                      </Typography>
                      {Boolean(songErrors.audio_file) && (
                        <Typography variant="caption" color="error.main">
                          {songErrors.audio_file}
                        </Typography>
                      )}
                    </Stack>
                    {songAudioName && (
                      <IconButton size="small" onClick={() => { setSongData('audio_file', null); setSongAudioName(''); }} title="Clear">
                        <ClearIcon fontSize="small" />
                      </IconButton>
                    )}
                  </Stack>
                </Paper>

                <Paper variant="outlined" sx={{ p: 1, borderRadius: 1.25, flex: 1 }}>
                  <Stack spacing={1} direction="row" alignItems="center" justifyContent="space-between">
                    <Stack spacing={0.5}>
                      <Button component="label" size="small" variant="outlined">
                        Upload Lyric (.txt)
                        <input hidden type="file" accept=".txt,text/plain" onChange={handleSongFileChange('lyric_file', setSongLyricName)} />
                      </Button>
                      <Typography variant="caption" color="text.secondary">
                        {songLyricName || 'No lyric file selected (max 10MB)'}
                      </Typography>
                      {Boolean(songErrors.lyric_file) && (
                        <Typography variant="caption" color="error.main">
                          {songErrors.lyric_file}
                        </Typography>
                      )}
                    </Stack>
                    {songLyricName && (
                      <IconButton size="small" onClick={() => { setSongData('lyric_file', null); setSongLyricName(''); }} title="Clear">
                        <ClearIcon fontSize="small" />
                      </IconButton>
                    )}
                  </Stack>
                </Paper>
              </Stack>

              <Paper variant="outlined" sx={{ p: 1, borderRadius: 1.25 }}>
                <Stack spacing={1}>
                  <Box sx={{ borderRadius: 1, overflow: 'hidden', bgcolor: 'action.hover', aspectRatio: '3 / 4', maxWidth: 160 }}>
                    {(songCoverPreview || editingSong?.image_url || editingSong?.thumbnail_url) && (
                      <Box component="img" src={songCoverPreview || editingSong?.image_url || editingSong?.thumbnail_url} alt="Cover" sx={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                    )}
                  </Box>
                  <Stack direction="row" alignItems="center" justifyContent="space-between">
                    <Button component="label" size="small" variant="outlined">
                      Upload & Crop Cover (3:4)
                      <input hidden type="file" accept="image/*" onChange={handleSongCoverChange} />
                    </Button>
                    {songImageName && (
                      <IconButton size="small" onClick={() => { setSongData('cover_file', null); setSongImageName(''); setSongCoverPreview(''); }} title="Clear">
                        <ClearIcon fontSize="small" />
                      </IconButton>
                    )}
                  </Stack>
                  <Typography variant="caption" color="text.secondary">
                    {songImageName || 'No image file selected (max 4MB)'}
                  </Typography>
                  {Boolean(songErrors.cover_file) && (
                    <Typography variant="caption" color="error.main">
                      {songErrors.cover_file}
                    </Typography>
                  )}
                </Stack>
              </Paper>


            </Stack>
          </DialogContent>
          <DialogActions>
            <Button onClick={() => setSongDialogOpen(false)} disabled={songProcessing}>
              Cancel
            </Button>
            <Button type="submit" variant="contained" disabled={songProcessing}>
              {editingSong ? 'Update' : 'Create'}
            </Button>
          </DialogActions>
        </Box>
      </Dialog>

      <Dialog open={audioPlayerOpen} onClose={() => setAudioPlayerOpen(false)} maxWidth="sm" fullWidth>
        <DialogTitle>Play Audio{audioPlayerTitle ? `: ${audioPlayerTitle}` : ''}</DialogTitle>
        <DialogContent dividers>
          {audioPlayerUrl ? (
            <Box component="audio" controls autoPlay src={audioPlayerUrl} sx={{ width: '100%' }} />
          ) : (
            <Typography variant="body2" color="text.secondary">
              No audio.
            </Typography>
          )}
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setAudioPlayerOpen(false)} size="small">
            Close
          </Button>
        </DialogActions>
      </Dialog>

      <Menu
        anchorEl={artistMenuAnchorEl}
        open={Boolean(artistMenuAnchorEl)}
        onClose={closeArtistMenu}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
        transformOrigin={{ vertical: 'top', horizontal: 'right' }}
      >
        <MenuItem
          onClick={() => {
            const row = artistMenuRow;
            closeArtistMenu();
            if (row) {
              openEditArtist(row);
            }
          }}
        >
          Edit
        </MenuItem>
        <MenuItem
          onClick={() => {
            const row = artistMenuRow;
            closeArtistMenu();
            if (row) {
              removeArtist(row);
            }
          }}
          sx={{ color: 'error.main' }}
        >
          Delete
        </MenuItem>
      </Menu>

      <Menu
        anchorEl={songMenuAnchorEl}
        open={Boolean(songMenuAnchorEl)}
        onClose={closeSongMenu}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
        transformOrigin={{ vertical: 'top', horizontal: 'right' }}
      >
        <MenuItem
          onClick={() => {
            const row = songMenuRow;
            closeSongMenu();
            if (row) {
              openEditSong(row);
            }
          }}
        >
          Edit
        </MenuItem>
        <MenuItem
          onClick={() => {
            const row = songMenuRow;
            closeSongMenu();
            if (row) {
              removeSong(row);
            }
          }}
          sx={{ color: 'error.main' }}
        >
          Delete
        </MenuItem>
      </Menu>

      <Snackbar open={Boolean(flash?.success)} autoHideDuration={3000} anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}>
        <Alert severity="success" variant="filled">
          {flash?.success || 'Action completed successfully.'}
        </Alert>
      </Snackbar>
    </Box>
  );
}

SongManagementWorkspace.layout = (page) => <AdminLayout children={page} />;
