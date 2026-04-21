import React, { useEffect, useMemo, useState } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import ImageCropper from '../../Components/Admin/ImageCropper';
import {
  Alert,
  Avatar,
  Box,
  Button,
  Autocomplete,
  Chip,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  Divider,
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
  TablePagination,
  TableRow,
  TextField,
  Typography,
  useMediaQuery,
  useTheme,
} from '@mui/material';
import {
  Add as AddIcon,
  Audiotrack as AudiotrackIcon,
  Clear as ClearIcon,
  Image as ImageIcon,
  Language as LanguageIcon,
  MenuBook as MenuBookIcon,
  LibraryBooks as LibraryBooksIcon,
  MoreVert as MoreVertIcon,
  SmartToy as SmartToyIcon,
  SportsEsports as SportsEsportsIcon,
  Style as StyleIcon,
  TextFields as TextFieldsIcon,
} from '@mui/icons-material';

const tabItems = [
  { key: 'word-of-day', label: 'Word of the day', icon: <MenuBookIcon fontSize="small" /> },
  { key: 'mini-library', label: 'Mini Library', icon: <LibraryBooksIcon fontSize="small" /> },
  { key: 'speaking-bot', label: 'Speaking Bot', icon: <SmartToyIcon fontSize="small" /> },
  { key: 'game-word', label: 'Game Word', icon: <SportsEsportsIcon fontSize="small" /> },
  { key: 'flashcard', label: 'Flashcard', icon: <StyleIcon fontSize="small" /> },
];

const defaultWordForm = {
  word: '',
  translation: '',
  speech: '',
  example: '',
  thumb_file: null,
  audio_file: null,
};

export default function ResourceManagementWorkspace({
  languages = [],
  selectedMajor = '',
  selectedLanguage = null,
  tab = 'word-of-day',
  wordOfDays = [],
  libraryCategories = [],
  libraryBooks = [],
  gameWords = [],
  speakingDialogueTitles = [],
  speakingDialogues = [],
  speakingDialogueTitleId = 0,
  flashcardDecks = [],
  flashcardCards = null,
  flashcardDeckId = 0,
}) {
  const { admin_app_url, flash } = usePage().props;
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('md'));
  const appBaseUrl = admin_app_url.replace(/\/admin\/?$/, '');

  const major = selectedMajor || (languages[0]?.code ?? '');
  const activeTab = tabItems.some((item) => item.key === tab) ? tab : 'word-of-day';
  const activeTabMeta = useMemo(() => tabItems.find((item) => item.key === activeTab), [activeTab]);

  const [thumbPreview, setThumbPreview] = useState('');
  const [thumbName, setThumbName] = useState('');
  const [thumbCropperOpen, setThumbCropperOpen] = useState(false);
  const [thumbCropTempImage, setThumbCropTempImage] = useState(null);
  const [audioName, setAudioName] = useState('');

  const [bookDialogOpen, setBookDialogOpen] = useState(false);
  const [editingBook, setEditingBook] = useState(null);
  const [bookSearch, setBookSearch] = useState('');
  const [categoryFilter, setCategoryFilter] = useState('all');
  const {
    data: bookData,
    setData: setBookData,
    post: postBook,
    processing: bookProcessing,
    errors: bookErrors,
    reset: resetBook,
    clearErrors: clearBookErrors,
  } = useForm({
    title: '',
    category: '',
    pdf_file: null,
    cover_file: null,
  });
  const [pdfName, setPdfName] = useState('');
  const [coverName, setCoverName] = useState('');
  const [coverPreview, setCoverPreview] = useState('');

  const selectedSpeakingTitleId = Number(speakingDialogueTitleId || 0);
  const [speakingTitleSearch, setSpeakingTitleSearch] = useState('');
  const [speakingDialogueSearch, setSpeakingDialogueSearch] = useState('');
  const filteredSpeakingTitles = useMemo(() => {
    const keyword = String(speakingTitleSearch || '').trim().toLowerCase();
    if (!keyword) {
      return speakingDialogueTitles || [];
    }
    return (speakingDialogueTitles || []).filter((t) => String(t.title || '').toLowerCase().includes(keyword));
  }, [speakingDialogueTitles, speakingTitleSearch]);
  const filteredSpeakingDialogues = useMemo(() => {
    const keyword = String(speakingDialogueSearch || '').trim().toLowerCase();
    if (!keyword) {
      return speakingDialogues || [];
    }
    return (speakingDialogues || []).filter((d) => {
      const a = String(d.person_a_text || '').toLowerCase();
      const at = String(d.person_a_translation || '').toLowerCase();
      const b = String(d.person_b_text || '').toLowerCase();
      const bt = String(d.person_b_translation || '').toLowerCase();
      return a.includes(keyword) || at.includes(keyword) || b.includes(keyword) || bt.includes(keyword);
    });
  }, [speakingDialogues, speakingDialogueSearch]);

  const selectedFlashcardDeckId = Number(flashcardDeckId || 0);
  const [flashcardCardSearchTouched, setFlashcardCardSearchTouched] = useState(false);
  const [flashcardCardSearch, setFlashcardCardSearch] = useState(() => {
    try {
      return new URL(window.location.href).searchParams.get('card_search') || '';
    } catch (e) {
      return '';
    }
  });
  const filteredFlashcardDecks = flashcardDecks || [];
  const flashcardCardsPaginator = useMemo(() => {
    return flashcardCards && !Array.isArray(flashcardCards) ? flashcardCards : null;
  }, [flashcardCards]);
  const flashcardCardRows = useMemo(() => {
    if (Array.isArray(flashcardCardsPaginator?.data)) {
      return flashcardCardsPaginator.data;
    }
    if (Array.isArray(flashcardCards)) {
      return flashcardCards;
    }
    return [];
  }, [flashcardCards, flashcardCardsPaginator]);
  const flashcardCardsTotal = useMemo(() => {
    return Number(flashcardCardsPaginator?.total ?? flashcardCardsPaginator?.meta?.total ?? flashcardCardRows.length);
  }, [flashcardCardsPaginator, flashcardCardRows.length]);
  const flashcardCardsPage = useMemo(() => {
    return Number(flashcardCardsPaginator?.current_page ?? flashcardCardsPaginator?.meta?.current_page ?? 1);
  }, [flashcardCardsPaginator]);
  const flashcardCardsPerPage = useMemo(() => {
    return Number(flashcardCardsPaginator?.per_page ?? flashcardCardsPaginator?.meta?.per_page ?? 25);
  }, [flashcardCardsPaginator]);
  const selectedFlashcardDeck = useMemo(() => {
    return (flashcardDecks || []).find((d) => Number(d.id) === selectedFlashcardDeckId) || null;
  }, [flashcardDecks, selectedFlashcardDeckId]);

  useEffect(() => {
    if (activeTab !== 'flashcard') {
      return undefined;
    }
    if (!flashcardCardSearchTouched) {
      return undefined;
    }

    const handle = window.setTimeout(() => {
      const params = new URLSearchParams();
      params.set('major', major);
      params.set('tab', 'flashcard');
      if (selectedFlashcardDeckId) {
        params.set('deck_id', String(selectedFlashcardDeckId));
      }
      const keyword = String(flashcardCardSearch || '').trim();
      if (keyword) {
        params.set('card_search', keyword);
      }
      params.set('cards_per_page', String(flashcardCardsPerPage || 25));
      params.set('cards_page', '1');

      router.visit(`${admin_app_url}/resources/workspace?${params.toString()}`, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
      });
    }, 350);

    return () => window.clearTimeout(handle);
  }, [
    activeTab,
    admin_app_url,
    flashcardCardSearch,
    flashcardCardSearchTouched,
    flashcardCardsPerPage,
    major,
    selectedFlashcardDeckId,
  ]);

  const [flashcardDeckDialogOpen, setFlashcardDeckDialogOpen] = useState(false);
  const [editingFlashcardDeck, setEditingFlashcardDeck] = useState(null);
  const {
    data: flashcardDeckData,
    setData: setFlashcardDeckData,
    post: postFlashcardDeck,
    processing: flashcardDeckProcessing,
    errors: flashcardDeckErrors,
    reset: resetFlashcardDeck,
    clearErrors: clearFlashcardDeckErrors,
  } = useForm({
    title: '',
    description: '',
  });

  const [flashcardCardDialogOpen, setFlashcardCardDialogOpen] = useState(false);
  const [editingFlashcardCard, setEditingFlashcardCard] = useState(null);
  const {
    data: flashcardCardData,
    setData: setFlashcardCardData,
    post: postFlashcardCard,
    processing: flashcardCardProcessing,
    errors: flashcardCardErrors,
    reset: resetFlashcardCard,
    clearErrors: clearFlashcardCardErrors,
  } = useForm({
    deck_id: selectedFlashcardDeckId ? String(selectedFlashcardDeckId) : '',
    word: '',
    burmese_translation: '',
    ipa: '',
    pronunciation_audio: '',
    image: '',
    parts_of_speech: '',
    example_sentences: '',
    synonyms: '',
    antonyms: '',
    relatived: '',
  });

  useEffect(() => {
    if (!editingFlashcardCard) {
      setFlashcardCardData('deck_id', selectedFlashcardDeckId ? String(selectedFlashcardDeckId) : '');
    }
  }, [selectedFlashcardDeckId, editingFlashcardCard, setFlashcardCardData]);

  const [flashcardBulkDialogOpen, setFlashcardBulkDialogOpen] = useState(false);
  const [flashcardBulkFileName, setFlashcardBulkFileName] = useState('');
  const {
    data: flashcardBulkData,
    setData: setFlashcardBulkData,
    post: postFlashcardBulk,
    processing: flashcardBulkProcessing,
    errors: flashcardBulkErrors,
    reset: resetFlashcardBulk,
    clearErrors: clearFlashcardBulkErrors,
  } = useForm({
    deck_id: selectedFlashcardDeckId ? String(selectedFlashcardDeckId) : '',
    cards_json: '',
    cards_file: null,
  });

  useEffect(() => {
    if (!flashcardBulkDialogOpen) {
      setFlashcardBulkData('deck_id', selectedFlashcardDeckId ? String(selectedFlashcardDeckId) : '');
    }
  }, [flashcardBulkDialogOpen, selectedFlashcardDeckId, setFlashcardBulkData]);

  const [flashcardDeckMenuAnchorEl, setFlashcardDeckMenuAnchorEl] = useState(null);
  const [flashcardDeckMenuRow, setFlashcardDeckMenuRow] = useState(null);
  const [flashcardCardMenuAnchorEl, setFlashcardCardMenuAnchorEl] = useState(null);
  const [flashcardCardMenuRow, setFlashcardCardMenuRow] = useState(null);

  const [speakingTitleDialogOpen, setSpeakingTitleDialogOpen] = useState(false);
  const [editingSpeakingTitle, setEditingSpeakingTitle] = useState(null);
  const {
    data: speakingTitleData,
    setData: setSpeakingTitleData,
    post: postSpeakingTitle,
    processing: speakingTitleProcessing,
    errors: speakingTitleErrors,
    reset: resetSpeakingTitle,
    clearErrors: clearSpeakingTitleErrors,
  } = useForm({
    title: '',
  });

  const [speakingDialogueDialogOpen, setSpeakingDialogueDialogOpen] = useState(false);
  const [editingSpeakingDialogue, setEditingSpeakingDialogue] = useState(null);
  const {
    data: speakingDialogueData,
    setData: setSpeakingDialogueData,
    post: postSpeakingDialogue,
    processing: speakingDialogueProcessing,
    errors: speakingDialogueErrors,
    reset: resetSpeakingDialogue,
    clearErrors: clearSpeakingDialogueErrors,
  } = useForm({
    speaking_dialogue_title_id: selectedSpeakingTitleId ? String(selectedSpeakingTitleId) : '',
    person_a_text: '',
    person_a_translation: '',
    person_b_text: '',
    person_b_translation: '',
    sort_order: 0,
  });

  const [speakingTitleMenuAnchorEl, setSpeakingTitleMenuAnchorEl] = useState(null);
  const [speakingTitleMenuRow, setSpeakingTitleMenuRow] = useState(null);
  const [speakingDialogueMenuAnchorEl, setSpeakingDialogueMenuAnchorEl] = useState(null);
  const [speakingDialogueMenuRow, setSpeakingDialogueMenuRow] = useState(null);

  const [gameWordTypeDialogOpen, setGameWordTypeDialogOpen] = useState(false);
  const [gameWordDialogOpen, setGameWordDialogOpen] = useState(false);
  const [editingGameWord, setEditingGameWord] = useState(null);
  const [gameWordType, setGameWordType] = useState('word');
  const {
    data: gameWordData,
    setData: setGameWordData,
    post: postGameWord,
    processing: gameWordProcessing,
    errors: gameWordErrors,
    reset: resetGameWord,
    clearErrors: clearGameWordErrors,
  } = useForm({
    type: 'word',
    display_word: '',
    a: '',
    b: '',
    c: '',
    ans: 'a',
    display_image_file: null,
    display_audio_file: null,
  });
  const [gameImagePreview, setGameImagePreview] = useState('');
  const [gameImageName, setGameImageName] = useState('');
  const [gameImageCropperOpen, setGameImageCropperOpen] = useState(false);
  const [gameImageCropTempImage, setGameImageCropTempImage] = useState(null);
  const [gameAudioName, setGameAudioName] = useState('');
  const [wordMenuAnchorEl, setWordMenuAnchorEl] = useState(null);
  const [wordMenuRow, setWordMenuRow] = useState(null);
  const [libraryMenuAnchorEl, setLibraryMenuAnchorEl] = useState(null);
  const [libraryMenuRow, setLibraryMenuRow] = useState(null);
  const [gameMenuAnchorEl, setGameMenuAnchorEl] = useState(null);
  const [gameMenuRow, setGameMenuRow] = useState(null);

  useEffect(() => {
    return () => {
      if (gameImagePreview && gameImagePreview.startsWith('blob:')) {
        URL.revokeObjectURL(gameImagePreview);
      }
    };
  }, [gameImagePreview]);

  useEffect(() => {
    return () => {
      if (gameImageCropTempImage && gameImageCropTempImage.startsWith('blob:')) {
        URL.revokeObjectURL(gameImageCropTempImage);
      }
    };
  }, [gameImageCropTempImage]);

  useEffect(() => {
    return () => {
      if (coverPreview && coverPreview.startsWith('blob:')) {
        URL.revokeObjectURL(coverPreview);
      }
    };
  }, [coverPreview]);

  const filteredLibraryBooks = useMemo(() => {
    const keyword = String(bookSearch || '').trim().toLowerCase();
    const selectedCategory = String(categoryFilter || 'all');
    return (libraryBooks || [])
      .filter((row) => {
        if (selectedCategory !== 'all' && String(row.category || '') !== selectedCategory) {
          return false;
        }
        if (!keyword) {
          return true;
        }
        return String(row.title || '').toLowerCase().includes(keyword);
      })
      .sort((a, b) => String(a.title || '').localeCompare(String(b.title || '')));
  }, [libraryBooks, categoryFilter, bookSearch]);

  const openCreateBook = () => {
    setEditingBook(null);
    clearBookErrors();
    resetBook();
    setBookData('title', '');
    setBookData('category', '');
    setBookData('pdf_file', null);
    setBookData('cover_file', null);
    setPdfName('');
    setCoverName('');
    setCoverPreview('');
    setBookDialogOpen(true);
  };

  const openEditBook = (row) => {
    setEditingBook(row);
    clearBookErrors();
    resetBook();
    setBookData('title', row.title || '');
    setBookData('category', row.category || '');
    setBookData('pdf_file', null);
    setBookData('cover_file', null);
    setPdfName('');
    setCoverName('');
    setCoverPreview(row.cover_image || '');
    setBookDialogOpen(true);
  };

  const submitBook = (event) => {
    event.preventDefault();
    if (!major) {
      return;
    }
    const query = `?major=${encodeURIComponent(major)}&tab=mini-library`;
    if (editingBook) {
      postBook(`${admin_app_url}/resources/mini-library/books/${editingBook.id}${query}`, {
        data: { ...bookData, _method: 'patch' },
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
          setBookDialogOpen(false);
          setEditingBook(null);
        },
      });
      return;
    }
    postBook(`${admin_app_url}/resources/mini-library/books${query}`, {
      data: bookData,
      preserveScroll: true,
      forceFormData: true,
      onSuccess: () => {
        setBookDialogOpen(false);
      },
    });
  };

  const removeBook = (row) => {
    if (!row) return;
    if (!confirm(`Delete book "${row.title}"?`)) return;
    router.delete(`${admin_app_url}/resources/mini-library/books/${row.id}?major=${encodeURIComponent(major)}&tab=mini-library`, {
      preserveScroll: true,
    });
  };

  const selectSpeakingDialogueTitle = (titleId) => {
    const id = Number(titleId || 0);
    router.visit(
      `${admin_app_url}/resources/workspace?major=${encodeURIComponent(major)}&tab=speaking-bot&speaking_dialogue_title_id=${encodeURIComponent(id)}`
    );
  };

  const openSpeakingTitleMenu = (event, row) => {
    event.stopPropagation();
    setSpeakingTitleMenuAnchorEl(event.currentTarget);
    setSpeakingTitleMenuRow(row);
  };

  const closeSpeakingTitleMenu = () => {
    setSpeakingTitleMenuAnchorEl(null);
    setSpeakingTitleMenuRow(null);
  };

  const openSpeakingDialogueMenu = (event, row) => {
    event.stopPropagation();
    setSpeakingDialogueMenuAnchorEl(event.currentTarget);
    setSpeakingDialogueMenuRow(row);
  };

  const closeSpeakingDialogueMenu = () => {
    setSpeakingDialogueMenuAnchorEl(null);
    setSpeakingDialogueMenuRow(null);
  };

  const openCreateSpeakingTitle = () => {
    setEditingSpeakingTitle(null);
    clearSpeakingTitleErrors();
    resetSpeakingTitle();
    setSpeakingTitleData('title', '');
    setSpeakingTitleDialogOpen(true);
  };

  const openEditSpeakingTitle = (row) => {
    setEditingSpeakingTitle(row);
    clearSpeakingTitleErrors();
    resetSpeakingTitle();
    setSpeakingTitleData('title', row?.title ?? '');
    setSpeakingTitleDialogOpen(true);
  };

  const submitSpeakingTitle = (event) => {
    event.preventDefault();
    if (!major) return;
    const query = `?major=${encodeURIComponent(major)}&tab=speaking-bot&speaking_dialogue_title_id=${encodeURIComponent(selectedSpeakingTitleId || 0)}`;
    if (editingSpeakingTitle) {
      postSpeakingTitle(`${admin_app_url}/resources/speaking-bot/titles/${editingSpeakingTitle.id}${query}`, {
        data: { ...speakingTitleData, _method: 'patch' },
        preserveScroll: true,
        onSuccess: () => {
          setSpeakingTitleDialogOpen(false);
          setEditingSpeakingTitle(null);
        },
      });
      return;
    }
    postSpeakingTitle(`${admin_app_url}/resources/speaking-bot/titles${query}`, {
      data: speakingTitleData,
      preserveScroll: true,
      onSuccess: () => {
        setSpeakingTitleDialogOpen(false);
      },
    });
  };

  const removeSpeakingTitle = (row) => {
    if (!row) return;
    if (!confirm(`Delete dialogue title "${row.title}"?`)) return;
    router.delete(
      `${admin_app_url}/resources/speaking-bot/titles/${row.id}?major=${encodeURIComponent(major)}&tab=speaking-bot&speaking_dialogue_title_id=${encodeURIComponent(selectedSpeakingTitleId || 0)}`,
      { preserveScroll: true }
    );
  };

  const openCreateSpeakingDialogue = () => {
    if (!selectedSpeakingTitleId) return;
    setEditingSpeakingDialogue(null);
    clearSpeakingDialogueErrors();
    resetSpeakingDialogue();
    setSpeakingDialogueData('speaking_dialogue_title_id', String(selectedSpeakingTitleId));
    setSpeakingDialogueData('person_a_text', '');
    setSpeakingDialogueData('person_a_translation', '');
    setSpeakingDialogueData('person_b_text', '');
    setSpeakingDialogueData('person_b_translation', '');
    setSpeakingDialogueData('sort_order', 0);
    setSpeakingDialogueDialogOpen(true);
  };

  const openEditSpeakingDialogue = (row) => {
    setEditingSpeakingDialogue(row);
    clearSpeakingDialogueErrors();
    resetSpeakingDialogue();
    setSpeakingDialogueData('speaking_dialogue_title_id', String(row?.speaking_dialogue_title_id ?? selectedSpeakingTitleId ?? ''));
    setSpeakingDialogueData('person_a_text', row?.person_a_text ?? '');
    setSpeakingDialogueData('person_a_translation', row?.person_a_translation ?? '');
    setSpeakingDialogueData('person_b_text', row?.person_b_text ?? '');
    setSpeakingDialogueData('person_b_translation', row?.person_b_translation ?? '');
    setSpeakingDialogueData('sort_order', row?.sort_order ?? 0);
    setSpeakingDialogueDialogOpen(true);
  };

  const submitSpeakingDialogue = (event) => {
    event.preventDefault();
    if (!major) return;
    const query = `?major=${encodeURIComponent(major)}&tab=speaking-bot&speaking_dialogue_title_id=${encodeURIComponent(selectedSpeakingTitleId || 0)}`;
    if (editingSpeakingDialogue) {
      postSpeakingDialogue(`${admin_app_url}/resources/speaking-bot/dialogues/${editingSpeakingDialogue.id}${query}`, {
        data: { ...speakingDialogueData, _method: 'patch' },
        preserveScroll: true,
        onSuccess: () => {
          setSpeakingDialogueDialogOpen(false);
          setEditingSpeakingDialogue(null);
        },
      });
      return;
    }
    postSpeakingDialogue(`${admin_app_url}/resources/speaking-bot/dialogues${query}`, {
      data: speakingDialogueData,
      preserveScroll: true,
      onSuccess: () => {
        setSpeakingDialogueDialogOpen(false);
      },
    });
  };

  const removeSpeakingDialogue = (row) => {
    if (!row) return;
    if (!confirm('Delete this dialogue?')) return;
    router.delete(
      `${admin_app_url}/resources/speaking-bot/dialogues/${row.id}?major=${encodeURIComponent(major)}&tab=speaking-bot&speaking_dialogue_title_id=${encodeURIComponent(selectedSpeakingTitleId || 0)}`,
      { preserveScroll: true }
    );
  };

  const buildFlashcardWorkspaceUrl = ({
    deckId = selectedFlashcardDeckId,
    cardsPage = flashcardCardsPage,
    cardsPerPage = flashcardCardsPerPage,
    cardSearch = flashcardCardSearch,
  } = {}) => {
    const params = new URLSearchParams();
    params.set('major', major);
    params.set('tab', 'flashcard');
    if (deckId) {
      params.set('deck_id', String(deckId));
    }
    const keyword = String(cardSearch || '').trim();
    if (keyword) {
      params.set('card_search', keyword);
    }
    if (cardsPerPage) {
      params.set('cards_per_page', String(cardsPerPage));
    }
    if (cardsPage) {
      params.set('cards_page', String(cardsPage));
    }
    return `${admin_app_url}/resources/workspace?${params.toString()}`;
  };

  const selectFlashcardDeck = (deckId) => {
    const id = Number(deckId || 0);
    setFlashcardCardSearchTouched(false);
    setFlashcardCardSearch('');
    router.visit(buildFlashcardWorkspaceUrl({ deckId: id, cardsPage: 1, cardSearch: '' }), {
      preserveScroll: true,
      preserveState: true,
    });
  };

  const openFlashcardDeckMenu = (event, row) => {
    event.stopPropagation();
    setFlashcardDeckMenuAnchorEl(event.currentTarget);
    setFlashcardDeckMenuRow(row);
  };

  const closeFlashcardDeckMenu = () => {
    setFlashcardDeckMenuAnchorEl(null);
    setFlashcardDeckMenuRow(null);
  };

  const openFlashcardCardMenu = (event, row) => {
    event.stopPropagation();
    setFlashcardCardMenuAnchorEl(event.currentTarget);
    setFlashcardCardMenuRow(row);
  };

  const closeFlashcardCardMenu = () => {
    setFlashcardCardMenuAnchorEl(null);
    setFlashcardCardMenuRow(null);
  };

  const openCreateFlashcardDeck = () => {
    setEditingFlashcardDeck(null);
    clearFlashcardDeckErrors();
    resetFlashcardDeck();
    setFlashcardDeckData('title', '');
    setFlashcardDeckData('description', '');
    setFlashcardDeckDialogOpen(true);
  };

  const openEditFlashcardDeck = (row) => {
    setEditingFlashcardDeck(row);
    clearFlashcardDeckErrors();
    resetFlashcardDeck();
    setFlashcardDeckData('title', row?.title ?? '');
    setFlashcardDeckData('description', row?.description ?? '');
    setFlashcardDeckDialogOpen(true);
  };

  const submitFlashcardDeck = (event) => {
    event.preventDefault();
    if (!major) return;
    const query = `?major=${encodeURIComponent(major)}&tab=flashcard&deck_id=${encodeURIComponent(selectedFlashcardDeckId || 0)}`;
    if (editingFlashcardDeck) {
      postFlashcardDeck(`${admin_app_url}/resources/flashcards/decks/${editingFlashcardDeck.id}${query}`, {
        data: { ...flashcardDeckData, _method: 'patch' },
        preserveScroll: true,
        onSuccess: () => {
          setFlashcardDeckDialogOpen(false);
          setEditingFlashcardDeck(null);
        },
      });
      return;
    }
    postFlashcardDeck(`${admin_app_url}/resources/flashcards/decks${query}`, {
      data: flashcardDeckData,
      preserveScroll: true,
      onSuccess: () => {
        setFlashcardDeckDialogOpen(false);
      },
    });
  };

  const removeFlashcardDeck = (row) => {
    if (!row) return;
    if (!confirm(`Delete deck "${row.title}"?`)) return;
    router.delete(`${admin_app_url}/resources/flashcards/decks/${row.id}?major=${encodeURIComponent(major)}&tab=flashcard&deck_id=${encodeURIComponent(selectedFlashcardDeckId || 0)}`, {
      preserveScroll: true,
    });
  };

  const openCreateFlashcardCard = () => {
    if (!selectedFlashcardDeckId) return;
    setEditingFlashcardCard(null);
    clearFlashcardCardErrors();
    resetFlashcardCard();
    setFlashcardCardData('deck_id', String(selectedFlashcardDeckId));
    setFlashcardCardData('word', '');
    setFlashcardCardData('burmese_translation', '');
    setFlashcardCardData('ipa', '');
    setFlashcardCardData('pronunciation_audio', '');
    setFlashcardCardData('image', '');
    setFlashcardCardData('parts_of_speech', '');
    setFlashcardCardData('example_sentences', '');
    setFlashcardCardData('synonyms', '');
    setFlashcardCardData('antonyms', '');
    setFlashcardCardData('relatived', '');
    setFlashcardCardDialogOpen(true);
  };

  const openFlashcardBulkUpload = () => {
    if (!selectedFlashcardDeckId) return;
    clearFlashcardBulkErrors();
    resetFlashcardBulk();
    setFlashcardBulkData('deck_id', String(selectedFlashcardDeckId));
    setFlashcardBulkData('cards_json', '');
    setFlashcardBulkData('cards_file', null);
    setFlashcardBulkFileName('');
    setFlashcardBulkDialogOpen(true);
  };

  const submitFlashcardBulkUpload = (event) => {
    event.preventDefault();
    if (!major) return;
    const deckId = Number(flashcardBulkData.deck_id || selectedFlashcardDeckId || 0);
    if (!deckId) return;

    const query = `?major=${encodeURIComponent(major)}&tab=flashcard&deck_id=${encodeURIComponent(deckId)}&cards_page=1`;
    postFlashcardBulk(`${admin_app_url}/resources/flashcards/cards/bulk${query}`, {
      data: flashcardBulkData,
      preserveScroll: true,
      forceFormData: Boolean(flashcardBulkData.cards_file),
      onSuccess: () => {
        setFlashcardBulkDialogOpen(false);
      },
    });
  };

  const openEditFlashcardCard = (row) => {
    setEditingFlashcardCard(row);
    clearFlashcardCardErrors();
    resetFlashcardCard();
    setFlashcardCardData('deck_id', String(row?.deck_id ?? selectedFlashcardDeckId ?? ''));
    setFlashcardCardData('word', row?.word ?? '');
    setFlashcardCardData('burmese_translation', row?.burmese_translation ?? '');
    setFlashcardCardData('ipa', row?.ipa ?? '');
    setFlashcardCardData('pronunciation_audio', row?.pronunciation_audio ?? '');
    setFlashcardCardData('image', row?.image ?? '');
    setFlashcardCardData('parts_of_speech', row?.parts_of_speech ?? '');
    setFlashcardCardData('example_sentences', row?.example_sentences ?? '');
    setFlashcardCardData('synonyms', row?.synonyms ?? '');
    setFlashcardCardData('antonyms', row?.antonyms ?? '');
    setFlashcardCardData('relatived', row?.relatived ?? '');
    setFlashcardCardDialogOpen(true);
  };

  const submitFlashcardCard = (event) => {
    event.preventDefault();
    if (!major) return;
    const query = `?major=${encodeURIComponent(major)}&tab=flashcard&deck_id=${encodeURIComponent(Number(flashcardCardData.deck_id || selectedFlashcardDeckId || 0))}`;
    if (editingFlashcardCard) {
      postFlashcardCard(`${admin_app_url}/resources/flashcards/cards/${editingFlashcardCard.id}${query}`, {
        data: { ...flashcardCardData, _method: 'patch' },
        preserveScroll: true,
        onSuccess: () => {
          setFlashcardCardDialogOpen(false);
          setEditingFlashcardCard(null);
        },
      });
      return;
    }
    postFlashcardCard(`${admin_app_url}/resources/flashcards/cards${query}`, {
      data: flashcardCardData,
      preserveScroll: true,
      onSuccess: () => {
        setFlashcardCardDialogOpen(false);
      },
    });
  };

  const removeFlashcardCard = (row) => {
    if (!row) return;
    if (!confirm(`Delete card "${row.word}"?`)) return;
    router.delete(
      `${admin_app_url}/resources/flashcards/cards/${row.id}?major=${encodeURIComponent(major)}&tab=flashcard&deck_id=${encodeURIComponent(selectedFlashcardDeckId || 0)}`,
      { preserveScroll: true }
    );
  };

  const handlePdfChange = (event) => {
    const file = event.target.files?.[0] || null;
    setBookData('pdf_file', file);
    setPdfName(file ? file.name : '');
  };

  const handleCoverChange = (event) => {
    const file = event.target.files?.[0] || null;
    setBookData('cover_file', file);
    setCoverName(file ? file.name : '');
    if (file) {
      const url = URL.createObjectURL(file);
      setCoverPreview((prev) => {
        if (prev && prev.startsWith('blob:')) {
          URL.revokeObjectURL(prev);
        }
        return url;
      });
    }
  };

  useEffect(() => {
    return () => {
      if (thumbPreview && thumbPreview.startsWith('blob:')) {
        URL.revokeObjectURL(thumbPreview);
      }
    };
  }, [thumbPreview]);

  useEffect(() => {
    return () => {
      if (thumbCropTempImage && thumbCropTempImage.startsWith('blob:')) {
        URL.revokeObjectURL(thumbCropTempImage);
      }
    };
  }, [thumbCropTempImage]);

  const [wordDialogOpen, setWordDialogOpen] = useState(false);
  const [editingWord, setEditingWord] = useState(null);
  const {
    data: wordData,
    setData: setWordData,
    post,
    processing,
    errors,
    reset,
    clearErrors,
  } = useForm(defaultWordForm);

  const openCreateWord = () => {
    setEditingWord(null);
    clearErrors();
    reset();
    setWordData(defaultWordForm);
    setThumbPreview('');
    setThumbName('');
    setThumbCropTempImage(null);
    setThumbCropperOpen(false);
    setAudioName('');
    setWordDialogOpen(true);
  };

  const openEditWord = (row) => {
    setEditingWord(row);
    clearErrors();
    setWordData({
      word: row.word || '',
      translation: row.translation || '',
      speech: row.speech || '',
      example: row.example || '',
      thumb_file: null,
      audio_file: null,
    });
    setThumbPreview(row.thumb || '');
    setThumbName('');
    setThumbCropTempImage(null);
    setThumbCropperOpen(false);
    setAudioName('');
    setWordDialogOpen(true);
  };

  const handleThumbChange = (event) => {
    const file = event.target.files?.[0] || null;
    if (!file) {
      setThumbName('');
      return;
    }
    setThumbName(file.name);
    const tmp = URL.createObjectURL(file);
    setThumbCropTempImage((prev) => {
      if (prev && prev.startsWith('blob:')) {
        URL.revokeObjectURL(prev);
      }
      return tmp;
    });
    setThumbCropperOpen(true);
  };

  const handleAudioChange = (event) => {
    const file = event.target.files?.[0] || null;
    setWordData('audio_file', file);
    setAudioName(file ? file.name : '');
  };
  const openWordMenu = (event, row) => {
    setWordMenuAnchorEl(event.currentTarget);
    setWordMenuRow(row);
  };
  const closeWordMenu = () => {
    setWordMenuAnchorEl(null);
    setWordMenuRow(null);
  };
  const openLibraryMenu = (event, row) => {
    setLibraryMenuAnchorEl(event.currentTarget);
    setLibraryMenuRow(row);
  };
  const closeLibraryMenu = () => {
    setLibraryMenuAnchorEl(null);
    setLibraryMenuRow(null);
  };
  const openGameMenu = (event, row) => {
    setGameMenuAnchorEl(event.currentTarget);
    setGameMenuRow(row);
  };
  const closeGameMenu = () => {
    setGameMenuAnchorEl(null);
    setGameMenuRow(null);
  };

  const submitWord = (event) => {
    event.preventDefault();
    if (!major) {
      return;
    }

    const query = `?major=${encodeURIComponent(major)}&tab=word-of-day`;
    if (editingWord) {
      post(`${admin_app_url}/resources/word-of-day/${editingWord.id}${query}`, {
        data: { ...wordData, _method: 'patch' },
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
          setWordDialogOpen(false);
          setEditingWord(null);
        },
      });
      return;
    }

    post(`${admin_app_url}/resources/word-of-day${query}`, {
      data: wordData,
      preserveScroll: true,
      forceFormData: true,
      onSuccess: () => {
        setWordDialogOpen(false);
      },
    });
  };

  const removeWord = (row) => {
    if (!row) return;
    if (!confirm(`Delete "${row.word}"?`)) return;
    router.delete(`${admin_app_url}/resources/word-of-day/${row.id}?major=${encodeURIComponent(major)}&tab=word-of-day`, {
      preserveScroll: true,
    });
  };

  const openGameWordTypeSelector = () => {
    setGameWordTypeDialogOpen(true);
  };

  const openCreateGameWord = (type) => {
    setEditingGameWord(null);
    clearGameWordErrors();
    resetGameWord();
    setGameWordType(type);
    setGameWordData('type', type);
    setGameWordData('display_word', '');
    setGameWordData('a', '');
    setGameWordData('b', '');
    setGameWordData('c', '');
    setGameWordData('ans', 'a');
    setGameWordData('display_image_file', null);
    setGameWordData('display_audio_file', null);
    setGameImagePreview('');
    setGameImageName('');
    setGameImageCropTempImage(null);
    setGameImageCropperOpen(false);
    setGameAudioName('');
    setGameWordDialogOpen(true);
  };

  const openEditGameWord = (row) => {
    const category = Number(row.category || 1);
    const type = category === 2 ? 'image' : category === 3 ? 'audio' : 'word';
    setEditingGameWord(row);
    clearGameWordErrors();
    resetGameWord();
    setGameWordType(type);
    setGameWordData('type', type);
    setGameWordData('display_word', row.display_word || '');
    setGameWordData('a', row.a || '');
    setGameWordData('b', row.b || '');
    setGameWordData('c', row.c || '');
    setGameWordData('ans', row.ans || 'a');
    setGameWordData('display_image_file', null);
    setGameWordData('display_audio_file', null);
    setGameImagePreview(row.display_image || '');
    setGameImageName('');
    setGameImageCropTempImage(null);
    setGameImageCropperOpen(false);
    setGameAudioName('');
    setGameWordDialogOpen(true);
  };

  const removeGameWord = (row) => {
    if (!row) return;
    if (!confirm(`Delete game word "${row.display_word}"?`)) return;
    router.delete(`${admin_app_url}/resources/game-words/${row.id}?major=${encodeURIComponent(major)}&tab=game-word`, { preserveScroll: true });
  };

  const submitGameWord = (event) => {
    event.preventDefault();
    if (!major) return;
    const query = `?major=${encodeURIComponent(major)}&tab=game-word`;
    if (editingGameWord) {
      postGameWord(`${admin_app_url}/resources/game-words/${editingGameWord.id}${query}`, {
        data: { ...gameWordData, _method: 'patch' },
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
          setGameWordDialogOpen(false);
          setEditingGameWord(null);
        },
      });
      return;
    }
    postGameWord(`${admin_app_url}/resources/game-words${query}`, {
      data: gameWordData,
      preserveScroll: true,
      forceFormData: true,
      onSuccess: () => {
        setGameWordDialogOpen(false);
      },
    });
  };

  const handleGameImageChange = (event) => {
    const file = event.target.files?.[0] || null;
    if (!file) {
      setGameImageName('');
      return;
    }
    setGameImageName(file.name);
    const tmp = URL.createObjectURL(file);
    setGameImageCropTempImage((prev) => {
      if (prev && prev.startsWith('blob:')) {
        URL.revokeObjectURL(prev);
      }
      return tmp;
    });
    setGameImageCropperOpen(true);
  };

  const handleGameAudioChange = (event) => {
    const file = event.target.files?.[0] || null;
    setGameWordData('display_audio_file', file);
    setGameAudioName(file ? file.name : '');
  };

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
    window.location.href = `${admin_app_url}/resources/workspace?major=${majorParam}${tabParam}`;
  };

  return (
    <Box>
      <Head title="Resource Management Workspace" />
      <Stack spacing={2.5}>
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              Resource Management Workspace
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Channel: {major || '-'}
            </Typography>
          </Box>
        </Box>

        <Box sx={{ display: { xs: 'block', md: 'flex' }, gap: 2, alignItems: 'flex-start' }}>
          <Stack spacing={1.5} sx={{ flex: 1, minWidth: 0 }}>
            {activeTab === 'word-of-day' ? (
              <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                <Stack direction="row" spacing={1} alignItems="center" justifyContent="space-between" sx={{ mb: 1 }}>
                  <Stack direction="row" spacing={1} alignItems="center">
                    {activeTabMeta?.icon}
                    <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                      Word of the day
                    </Typography>
                  </Stack>
                  <Button size="small" variant="contained" startIcon={<AddIcon />} onClick={openCreateWord}>
                    Add Word
                  </Button>
                </Stack>

                <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2, overflowX: 'auto' }}>
                  <Table
                    size="small"
                    sx={{
                      tableLayout: 'fixed',
                      '& .MuiTableCell-root': { px: 1, py: 0.75 },
                    }}
                  >
                    <TableHead>
                      <TableRow>
                        <TableCell sx={{ fontWeight: 700 }}>Word</TableCell>
                        <TableCell sx={{ fontWeight: 700, width: 200 }}>Translation</TableCell>
                        <TableCell sx={{ fontWeight: 700, width: 110 }}>Speech</TableCell>
                        <TableCell sx={{ fontWeight: 700, width: 64 }}>Thumb</TableCell>
                        <TableCell align="right" sx={{ fontWeight: 700, width: 96 }}>Actions</TableCell>
                      </TableRow>
                    </TableHead>
                    <TableBody>
                      {wordOfDays.map((row) => (
                        <TableRow key={row.id} hover>
                          <TableCell>
                            <Typography variant="body2" sx={{ fontWeight: 700 }}>
                              {row.word}
                            </Typography>
                            <Typography variant="caption" color="text.secondary">
                              ID {row.id}
                            </Typography>
                          </TableCell>
                          <TableCell>{row.translation}</TableCell>
                          <TableCell>{row.speech || '-'}</TableCell>
                          <TableCell>
                            <Avatar
                              variant="rounded"
                              src={row.thumb || ''}
                              sx={{ width: 36, height: 36, bgcolor: 'action.selected' }}
                            >
                              <ImageIcon fontSize="small" />
                            </Avatar>
                          </TableCell>
                          <TableCell align="right" sx={{ whiteSpace: 'nowrap' }}>
                            <IconButton size="small" onClick={(e) => openWordMenu(e, row)} title="Actions" sx={{ p: 0.5 }}>
                              <MoreVertIcon fontSize="small" />
                            </IconButton>
                          </TableCell>
                        </TableRow>
                      ))}
                      {wordOfDays.length === 0 && (
                        <TableRow>
                          <TableCell colSpan={5}>
                            <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                              No words found.
                            </Typography>
                          </TableCell>
                        </TableRow>
                      )}
                    </TableBody>
                  </Table>
                </TableContainer>
              </Paper>
            ) : activeTab === 'mini-library' ? (
              <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                <Stack direction="row" spacing={1} alignItems="center" justifyContent="space-between" sx={{ mb: 1 }}>
                  <Stack direction="row" spacing={1} alignItems="center">
                    {activeTabMeta?.icon}
                    <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                      Mini Library
                    </Typography>
                  </Stack>
                  <Button size="small" variant="contained" startIcon={<AddIcon />} onClick={openCreateBook}>
                    Add Book
                  </Button>
                </Stack>

                <Paper variant="outlined" sx={{ p: 1, borderRadius: 1.25, mb: 1 }}>
                  <Stack direction={{ xs: 'column', sm: 'row' }} spacing={1}>
                    <TextField
                      size="small"
                      select
                      label="Category"
                      value={categoryFilter}
                      onChange={(e) => setCategoryFilter(e.target.value)}
                      sx={{ minWidth: 220 }}
                    >
                      <MenuItem value="all">All</MenuItem>
                      {libraryCategories.map((c) => (
                        <MenuItem key={`lib-cat-${c}`} value={c}>
                          {c}
                        </MenuItem>
                      ))}
                    </TextField>
                    <TextField
                      size="small"
                      label="Search book"
                      value={bookSearch}
                      onChange={(e) => setBookSearch(e.target.value)}
                      fullWidth
                    />
                  </Stack>
                </Paper>

                <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2, overflowX: 'auto' }}>
                  <Table
                    size="small"
                    sx={{
                      tableLayout: 'fixed',
                      '& .MuiTableCell-root': { px: 1, py: 0.75 },
                    }}
                  >
                    <TableHead>
                      <TableRow>
                        <TableCell sx={{ fontWeight: 700 }}>Book</TableCell>
                        <TableCell sx={{ fontWeight: 700, width: 160 }}>Category</TableCell>
                        <TableCell sx={{ fontWeight: 700, width: 64 }}>Cover</TableCell>
                        <TableCell sx={{ fontWeight: 700, width: 90 }}>PDF</TableCell>
                        <TableCell align="right" sx={{ fontWeight: 700, width: 96 }}>Actions</TableCell>
                      </TableRow>
                    </TableHead>
                    <TableBody>
                      {filteredLibraryBooks.map((row) => (
                        <TableRow key={row.id} hover>
                          <TableCell>
                            <Typography variant="body2" sx={{ fontWeight: 700 }}>
                              {row.title}
                            </Typography>
                            <Typography variant="caption" color="text.secondary">
                              ID {row.id}
                            </Typography>
                          </TableCell>
                          <TableCell>{row.category || '-'}</TableCell>
                          <TableCell>
                            <Avatar
                              variant="rounded"
                              src={row.cover_image || ''}
                              sx={{ width: 36, height: 36, bgcolor: 'action.selected' }}
                            >
                              <ImageIcon fontSize="small" />
                            </Avatar>
                          </TableCell>
                          <TableCell>
                            {row.pdf_url ? (
                              <Button size="small" variant="outlined" onClick={() => window.open(row.pdf_url, '_blank', 'noreferrer')}>
                                Open
                              </Button>
                            ) : (
                              <Typography variant="body2" color="text.secondary">-</Typography>
                            )}
                          </TableCell>
                          <TableCell align="right" sx={{ whiteSpace: 'nowrap' }}>
                            <IconButton size="small" onClick={(e) => openLibraryMenu(e, row)} title="Actions" sx={{ p: 0.5 }}>
                              <MoreVertIcon fontSize="small" />
                            </IconButton>
                          </TableCell>
                        </TableRow>
                      ))}
                      {filteredLibraryBooks.length === 0 && (
                        <TableRow>
                          <TableCell colSpan={5}>
                            <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                              No books found.
                            </Typography>
                          </TableCell>
                        </TableRow>
                      )}
                    </TableBody>
                  </Table>
                </TableContainer>
              </Paper>
            ) : activeTab === 'speaking-bot' ? (
              <Box sx={{ display: { xs: 'block', md: 'grid' }, gridTemplateColumns: { md: '300px 1fr' }, gap: 1.5 }}>
                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                  <Stack direction="row" spacing={1} alignItems="center" justifyContent="space-between" sx={{ mb: 1 }}>
                    <Stack direction="row" spacing={1} alignItems="center" sx={{ minWidth: 0 }}>
                      {activeTabMeta?.icon}
                      <Typography variant="subtitle1" sx={{ fontWeight: 700 }} noWrap>
                        Dialogue Titles
                      </Typography>
                      <Chip size="small" label={`Total: ${Number(speakingDialogueTitles?.length || 0)}`} variant="outlined" />
                    </Stack>
                    <Button size="small" variant="contained" startIcon={<AddIcon />} onClick={openCreateSpeakingTitle}>
                      Add
                    </Button>
                  </Stack>

                  <TextField
                    size="small"
                    label="Search title"
                    value={speakingTitleSearch}
                    onChange={(e) => setSpeakingTitleSearch(e.target.value)}
                    fullWidth
                    sx={{ mb: 1 }}
                  />

                  <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2, overflowX: 'auto' }}>
                    <Table size="small" sx={{ tableLayout: 'fixed', '& .MuiTableCell-root': { px: 0.5, py: 0.375 } }}>
                      <TableHead>
                        <TableRow>
                          <TableCell sx={{ fontWeight: 700, width: 120 }}>Title</TableCell>
                          <TableCell align="right" sx={{ fontWeight: 700, width: 32 }}>Actions</TableCell>
                        </TableRow>
                      </TableHead>
                      <TableBody>
                        {filteredSpeakingTitles.map((t) => (
                          <TableRow
                            key={`title-${t.id}`}
                            hover
                            selected={Number(t.id) === selectedSpeakingTitleId}
                            onClick={() => selectSpeakingDialogueTitle(t.id)}
                            sx={{ cursor: 'pointer' }}
                          >
                            <TableCell>
                              <Typography variant="body2" sx={{ fontWeight: 700 }} noWrap title={t.title || ''}>
                                {t.title}
                              </Typography>
                            </TableCell>
                            <TableCell align="right" sx={{ whiteSpace: 'nowrap' }}>
                              <IconButton size="small" onClick={(e) => openSpeakingTitleMenu(e, t)} title="Actions" sx={{ p: 0.25 }}>
                                <MoreVertIcon fontSize="small" />
                              </IconButton>
                            </TableCell>
                          </TableRow>
                        ))}
                        {filteredSpeakingTitles.length === 0 && (
                          <TableRow>
                            <TableCell colSpan={2}>
                              <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                                No titles found.
                              </Typography>
                            </TableCell>
                          </TableRow>
                        )}
                      </TableBody>
                    </Table>
                  </TableContainer>
                </Paper>

                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2, mt: { xs: 1.5, md: 0 } }}>
                  <Stack direction="row" spacing={1} alignItems="center" justifyContent="space-between" sx={{ mb: 1 }}>
                    <Stack direction="row" spacing={1} alignItems="center" sx={{ minWidth: 0 }}>
                      <Typography variant="subtitle1" sx={{ fontWeight: 700 }} noWrap>
                        Dialogues
                      </Typography>
                      {selectedSpeakingTitleId ? (
                        <Chip size="small" label={`Title ID: ${selectedSpeakingTitleId}`} variant="outlined" />
                      ) : (
                        <Chip size="small" label="Select a title" variant="outlined" />
                      )}
                      <Chip size="small" label={`Total: ${Number(speakingDialogues?.length || 0)}`} variant="outlined" />
                    </Stack>
                    <Button
                      size="small"
                      variant="contained"
                      startIcon={<AddIcon />}
                      onClick={openCreateSpeakingDialogue}
                      disabled={!selectedSpeakingTitleId}
                    >
                      Add
                    </Button>
                  </Stack>

                  <TextField
                    size="small"
                    label="Search dialogue"
                    value={speakingDialogueSearch}
                    onChange={(e) => setSpeakingDialogueSearch(e.target.value)}
                    fullWidth
                    sx={{ mb: 1 }}
                    disabled={!selectedSpeakingTitleId}
                  />

                  <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2, overflowX: 'auto' }}>
                    <Table size="small" sx={{ tableLayout: 'fixed', '& .MuiTableCell-root': { px: 1, py: 0.75 } }}>
                      <TableHead>
                        <TableRow>
                          <TableCell sx={{ fontWeight: 700, width: 64 }}>Sort</TableCell>
                          <TableCell sx={{ fontWeight: 700 }}>Person A</TableCell>
                          <TableCell sx={{ fontWeight: 700 }}>Person B</TableCell>
                          <TableCell align="right" sx={{ fontWeight: 700, width: 56 }}>Actions</TableCell>
                        </TableRow>
                      </TableHead>
                      <TableBody>
                        {filteredSpeakingDialogues.map((d) => (
                          <TableRow key={`dlg-${d.id}`} hover>
                            <TableCell>
                              <Chip size="small" label={Number(d.sort_order || 0)} variant="outlined" />
                            </TableCell>
                            <TableCell>
                              <Typography variant="body2" sx={{ fontWeight: 600 }} noWrap title={d.person_a_text || ''}>
                                {d.person_a_text}
                              </Typography>
                              {d.person_a_translation && (
                                <Typography variant="caption" color="text.secondary" noWrap title={d.person_a_translation || ''} sx={{ display: 'block' }}>
                                  {d.person_a_translation}
                                </Typography>
                              )}
                            </TableCell>
                            <TableCell>
                              <Typography variant="body2" sx={{ fontWeight: 600 }} noWrap title={d.person_b_text || ''}>
                                {d.person_b_text}
                              </Typography>
                              {d.person_b_translation && (
                                <Typography variant="caption" color="text.secondary" noWrap title={d.person_b_translation || ''} sx={{ display: 'block' }}>
                                  {d.person_b_translation}
                                </Typography>
                              )}
                            </TableCell>
                            <TableCell align="right" sx={{ whiteSpace: 'nowrap' }}>
                              <IconButton size="small" onClick={(e) => openSpeakingDialogueMenu(e, d)} title="Actions">
                                <MoreVertIcon fontSize="small" />
                              </IconButton>
                            </TableCell>
                          </TableRow>
                        ))}
                        {filteredSpeakingDialogues.length === 0 && (
                          <TableRow>
                            <TableCell colSpan={4}>
                              <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                                {selectedSpeakingTitleId ? 'No dialogues found.' : 'Select a title to view dialogues.'}
                              </Typography>
                            </TableCell>
                          </TableRow>
                        )}
                      </TableBody>
                    </Table>
                  </TableContainer>
                </Paper>
              </Box>
            ) : activeTab === 'game-word' ? (
              <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                <Stack direction="row" spacing={1} alignItems="center" justifyContent="space-between" sx={{ mb: 1 }}>
                  <Stack direction="row" spacing={1} alignItems="center">
                    {activeTabMeta?.icon}
                    <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                      Game Word
                    </Typography>
                    <Chip size="small" label={`Total: ${Number(gameWords?.length || 0)}`} variant="outlined" />
                  </Stack>
                  <Button size="small" variant="contained" startIcon={<AddIcon />} onClick={openGameWordTypeSelector}>
                    Add Game Word
                  </Button>
                </Stack>

                <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2, overflowX: 'auto' }}>
                  <Table
                    size="small"
                    sx={{
                      tableLayout: 'fixed',
                      '& .MuiTableCell-root': { px: 1, py: 0.75 },
                    }}
                  >
                    <TableHead>
                      <TableRow>
                        <TableCell sx={{ fontWeight: 700, width: 84 }}>Type</TableCell>
                        <TableCell sx={{ fontWeight: 700 }}>Prompt</TableCell>
                        <TableCell sx={{ fontWeight: 700 }}>Options</TableCell>
                        <TableCell sx={{ fontWeight: 700, width: 56 }}>Ans</TableCell>
                        <TableCell align="right" sx={{ fontWeight: 700, width: 96 }}>Actions</TableCell>
                      </TableRow>
                    </TableHead>
                    <TableBody>
                      {gameWords.map((row) => {
                        const category = Number(row.category || 1);
                        const typeLabel = category === 2 ? 'Image' : category === 3 ? 'Audio' : 'Word';
                        return (
                          <TableRow key={row.id} hover>
                            <TableCell>
                              <Stack direction="row" spacing={0.5} alignItems="center">
                                {category === 2 ? <ImageIcon fontSize="small" /> : category === 3 ? <AudiotrackIcon fontSize="small" /> : <TextFieldsIcon fontSize="small" />}
                                <Typography variant="body2" noWrap>{typeLabel}</Typography>
                              </Stack>
                            </TableCell>
                            <TableCell>
                              <Stack direction="row" spacing={1} alignItems="center">
                                {category === 2 ? (
                                  <Avatar variant="rounded" src={row.display_image || ''} sx={{ width: 44, height: 26, bgcolor: 'action.selected' }}>
                                    <ImageIcon fontSize="small" />
                                  </Avatar>
                                ) : category === 3 ? (
                                  <IconButton
                                    size="small"
                                    disabled={!row.display_audio}
                                    onClick={() => {
                                      if (row.display_audio) {
                                        window.open(row.display_audio, '_blank', 'noreferrer');
                                      }
                                    }}
                                    title="Open audio"
                                  >
                                    <AudiotrackIcon fontSize="small" />
                                  </IconButton>
                                ) : null}
                                <Box sx={{ minWidth: 0 }}>
                                  <Typography variant="body2" sx={{ fontWeight: 700 }} noWrap title={row.display_word || ''}>
                                    {row.display_word}
                                  </Typography>
                                  <Typography variant="caption" color="text.secondary" noWrap sx={{ display: 'block' }}>
                                    ID {row.id}
                                  </Typography>
                                </Box>
                              </Stack>
                            </TableCell>
                            <TableCell>
                              <Typography
                                variant="body2"
                                color="text.secondary"
                                noWrap
                                sx={{ maxWidth: 220, overflow: 'hidden', textOverflow: 'ellipsis' }}
                                title={`A: ${row.a} • B: ${row.b} • C: ${row.c}`}
                              >
                                A: {row.a} • B: {row.b} • C: {row.c}
                              </Typography>
                            </TableCell>
                            <TableCell>
                              <Chip size="small" label={String(row.ans || '').toUpperCase()} />
                            </TableCell>
                            <TableCell align="right" sx={{ whiteSpace: 'nowrap' }}>
                              <IconButton size="small" onClick={(e) => openGameMenu(e, row)} title="Actions" sx={{ p: 0.5 }}>
                                <MoreVertIcon fontSize="small" />
                              </IconButton>
                            </TableCell>
                          </TableRow>
                        );
                      })}
                      {gameWords.length === 0 && (
                        <TableRow>
                          <TableCell colSpan={5}>
                            <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                              No game words found.
                            </Typography>
                          </TableCell>
                        </TableRow>
                      )}
                    </TableBody>
                  </Table>
                </TableContainer>
              </Paper>
            ) : activeTab === 'flashcard' ? (
              <Stack spacing={1.5}>
                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                  <Stack direction="row" spacing={1} alignItems="center" justifyContent="space-between" sx={{ mb: 1 }}>
                    <Stack direction="row" spacing={1} alignItems="center" sx={{ minWidth: 0 }}>
                      {activeTabMeta?.icon}
                      <Typography variant="subtitle1" sx={{ fontWeight: 700 }} noWrap>
                        Decks
                      </Typography>
                      <Chip size="small" label={`Total: ${Number(flashcardDecks?.length || 0)}`} variant="outlined" />
                    </Stack>
                    <Button size="small" variant="contained" startIcon={<AddIcon />} onClick={openCreateFlashcardDeck}>
                      Add
                    </Button>
                  </Stack>

                  <Box
                    sx={{
                      display: 'grid',
                      gridTemplateColumns: {
                        xs: 'repeat(2, minmax(0, 1fr))',
                        sm: 'repeat(3, minmax(0, 1fr))',
                        md: 'repeat(4, minmax(0, 1fr))',
                        lg: 'repeat(5, minmax(0, 1fr))',
                      },
                      gap: 0.75,
                    }}
                  >
                    {filteredFlashcardDecks.map((d) => {
                      const selected = Number(d.id) === selectedFlashcardDeckId;
                      return (
                        <Paper
                          key={`deck-${d.id}`}
                          variant={selected ? 'elevation' : 'outlined'}
                          elevation={selected ? 2 : 0}
                          sx={{
                            p: 0.75,
                            borderRadius: 1.25,
                            cursor: 'pointer',
                            borderColor: selected ? 'primary.main' : 'divider',
                            position: 'relative',
                          }}
                          onClick={() => selectFlashcardDeck(d.id)}
                        >
                          <IconButton
                            size="small"
                            onClick={(e) => openFlashcardDeckMenu(e, d)}
                            title="Actions"
                            sx={{ position: 'absolute', top: 4, right: 4, p: 0.25 }}
                          >
                            <MoreVertIcon fontSize="small" />
                          </IconButton>
                          <Stack spacing={0.5}>
                            <Box
                              sx={{
                                width: '100%',
                                aspectRatio: '1 / 1',
                                borderRadius: 1,
                                bgcolor: 'action.hover',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                overflow: 'hidden',
                              }}
                            >
                              <StyleIcon color={selected ? 'primary' : 'action'} />
                            </Box>
                            <Typography variant="body2" sx={{ fontWeight: 700 }} noWrap title={d.title || ''}>
                              {d.title}
                            </Typography>
                            <Typography variant="caption" color="text.secondary" noWrap title={d.description || ''}>
                              {d.description || `ID ${d.id}`}
                            </Typography>
                          </Stack>
                        </Paper>
                      );
                    })}
                  </Box>

                  {filteredFlashcardDecks.length === 0 && (
                    <Typography variant="body2" color="text.secondary" sx={{ mt: 1, textAlign: 'center' }}>
                      No decks found.
                    </Typography>
                  )}
                </Paper>

                <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                  <Stack direction="row" spacing={1} alignItems="center" justifyContent="space-between" sx={{ mb: 1 }}>
                    <Stack direction="row" spacing={1} alignItems="center" sx={{ minWidth: 0 }}>
                      <Typography variant="subtitle1" sx={{ fontWeight: 700 }} noWrap>
                        Cards
                      </Typography>
                      {selectedFlashcardDeck ? (
                        <Chip size="small" label={selectedFlashcardDeck.title} variant="outlined" />
                      ) : (
                        <Chip size="small" label="Select a deck" variant="outlined" />
                      )}
                      <Chip size="small" label={`Total: ${flashcardCardsTotal}`} variant="outlined" />
                    </Stack>
                    <Stack direction="row" spacing={1}>
                      <Button
                        size="small"
                        variant="outlined"
                        onClick={openFlashcardBulkUpload}
                        disabled={!selectedFlashcardDeckId}
                      >
                        Bulk Upload
                      </Button>
                      <Button
                        size="small"
                        variant="contained"
                        startIcon={<AddIcon />}
                        onClick={openCreateFlashcardCard}
                        disabled={!selectedFlashcardDeckId}
                      >
                        Add
                      </Button>
                    </Stack>
                  </Stack>

                  <TextField
                    size="small"
                    label="Search card"
                    value={flashcardCardSearch}
                    onChange={(e) => {
                      setFlashcardCardSearchTouched(true);
                      setFlashcardCardSearch(e.target.value);
                    }}
                    fullWidth
                    sx={{ mb: 1 }}
                    disabled={!selectedFlashcardDeckId}
                  />

                  <TableContainer component={Paper} variant="outlined" sx={{ borderRadius: 2, overflowX: 'auto' }}>
                    <Table size="small" sx={{ tableLayout: 'fixed', '& .MuiTableCell-root': { px: 1, py: 0.75 } }}>
                      <TableHead>
                        <TableRow>
                          <TableCell sx={{ fontWeight: 700 }}>Word</TableCell>
                          <TableCell sx={{ fontWeight: 700, width: 220 }}>Translation</TableCell>
                          <TableCell sx={{ fontWeight: 700, width: 140 }}>IPA</TableCell>
                          <TableCell sx={{ fontWeight: 700, width: 64 }}>Img</TableCell>
                          <TableCell align="right" sx={{ fontWeight: 700, width: 64 }}>Actions</TableCell>
                        </TableRow>
                      </TableHead>
                      <TableBody>
                        {flashcardCardRows.map((c) => (
                          <TableRow key={`card-${c.id}`} hover>
                            <TableCell>
                              <Typography variant="body2" sx={{ fontWeight: 700 }} noWrap title={c.word || ''}>
                                {c.word}
                              </Typography>
                              <Typography variant="caption" color="text.secondary" noWrap sx={{ display: 'block' }}>
                                ID {c.id}
                              </Typography>
                            </TableCell>
                            <TableCell>
                              <Typography variant="body2" color="text.secondary" noWrap title={c.burmese_translation || ''}>
                                {c.burmese_translation || '-'}
                              </Typography>
                            </TableCell>
                            <TableCell>
                              <Typography variant="body2" color="text.secondary" noWrap title={c.ipa || ''}>
                                {c.ipa || '-'}
                              </Typography>
                            </TableCell>
                            <TableCell>
                              <Avatar variant="rounded" src={c.image || ''} sx={{ width: 36, height: 36, bgcolor: 'action.selected' }}>
                                <ImageIcon fontSize="small" />
                              </Avatar>
                            </TableCell>
                            <TableCell align="right" sx={{ whiteSpace: 'nowrap' }}>
                              <IconButton size="small" onClick={(e) => openFlashcardCardMenu(e, c)} title="Actions">
                                <MoreVertIcon fontSize="small" />
                              </IconButton>
                            </TableCell>
                          </TableRow>
                        ))}
                        {flashcardCardRows.length === 0 && (
                          <TableRow>
                            <TableCell colSpan={5}>
                              <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                                {selectedFlashcardDeckId ? 'No cards found.' : 'Select a deck to view cards.'}
                              </Typography>
                            </TableCell>
                          </TableRow>
                        )}
                      </TableBody>
                    </Table>
                  </TableContainer>
                  {selectedFlashcardDeckId ? (
                    <TablePagination
                      component="div"
                      count={flashcardCardsTotal}
                      page={Math.max(0, flashcardCardsPage - 1)}
                      rowsPerPage={flashcardCardsPerPage}
                      rowsPerPageOptions={[10, 25, 50, 100]}
                      onPageChange={(_, nextPage) => {
                        router.visit(buildFlashcardWorkspaceUrl({ cardsPage: nextPage + 1 }), {
                          preserveScroll: true,
                          preserveState: true,
                        });
                      }}
                      onRowsPerPageChange={(event) => {
                        const nextValue = Number(event.target.value || 25);
                        router.visit(buildFlashcardWorkspaceUrl({ cardsPerPage: nextValue, cardsPage: 1 }), {
                          preserveScroll: true,
                          preserveState: true,
                        });
                      }}
                    />
                  ) : null}
                </Paper>
              </Stack>
            ) : (
              <Paper variant="outlined" sx={{ p: 2, borderRadius: 2 }}>
                <Stack direction="row" spacing={1} alignItems="center" sx={{ mb: 0.75 }}>
                  {activeTabMeta?.icon}
                  <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                    {activeTabMeta?.label || 'Resources'}
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
              width: { xs: '100%', md: 260 },
            }}
          >
            <Box sx={{ p: 1, borderBottom: '1px solid', borderColor: 'divider' }}>
              <Stack direction="row" spacing={1.25} alignItems="center">
                <Avatar
                  src={buildImageUrl(selectedLanguage?.image_path)}
                  sx={{ width: 30, height: 30, bgcolor: selectedLanguage?.primary_color || 'action.selected' }}
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
              <Divider sx={{ my: 0.75 }} />
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
            <List dense sx={{ p: 0.75 }}>
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

      <Dialog open={wordDialogOpen} onClose={() => setWordDialogOpen(false)} maxWidth="sm" fullWidth>
        <DialogTitle>{editingWord ? 'Edit Word' : 'Add Word'}</DialogTitle>
        <Box component="form" onSubmit={submitWord}>
          <DialogContent dividers>
            <Stack spacing={1}>
              <TextField
                label="Word"
                value={wordData.word}
                onChange={(e) => setWordData('word', e.target.value)}
                error={Boolean(errors.word)}
                helperText={errors.word}
                fullWidth
                size="small"
              />
              <TextField
                label="Translation"
                value={wordData.translation}
                onChange={(e) => setWordData('translation', e.target.value)}
                error={Boolean(errors.translation)}
                helperText={errors.translation}
                fullWidth
                size="small"
              />
              <TextField
                label="Speech"
                value={wordData.speech}
                onChange={(e) => setWordData('speech', e.target.value)}
                error={Boolean(errors.speech)}
                helperText={errors.speech}
                fullWidth
                size="small"
              />
              <TextField
                label="Example"
                value={wordData.example}
                onChange={(e) => setWordData('example', e.target.value)}
                error={Boolean(errors.example)}
                helperText={errors.example}
                fullWidth
                multiline
                minRows={3}
              />
              <Paper variant="outlined" sx={{ p: 1, borderRadius: 1.25 }}>
                <Stack spacing={1}>
                  <Box sx={{ borderRadius: 1, overflow: 'hidden', bgcolor: 'action.hover', aspectRatio: '16 / 9', maxWidth: 320 }}>
                    {thumbPreview && (
                      <Box component="img" src={thumbPreview} alt="Thumb" sx={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                    )}
                    {!thumbPreview && (
                      <Stack alignItems="center" justifyContent="center" sx={{ width: '100%', height: '100%' }}>
                        <ImageIcon color="action" />
                      </Stack>
                    )}
                  </Box>
                  <Stack direction="row" alignItems="center" justifyContent="space-between">
                    <Button component="label" size="small" variant="outlined">
                      Upload & Crop Image (16:9)
                      <input hidden type="file" accept="image/*" onChange={handleThumbChange} />
                    </Button>
                    {(thumbName || thumbPreview) && (
                      <IconButton
                        size="small"
                        onClick={() => {
                          setWordData('thumb_file', null);
                          setThumbName('');
                          setThumbPreview('');
                        }}
                        title="Clear"
                      >
                        <ClearIcon fontSize="small" />
                      </IconButton>
                    )}
                  </Stack>
                  <Typography variant="caption" color="text.secondary">
                    {thumbName || (editingWord?.thumb ? 'Current image' : 'No image selected')}
                  </Typography>
                  {Boolean(errors.thumb_file) && (
                    <Typography variant="caption" color="error.main">
                      {errors.thumb_file}
                    </Typography>
                  )}
                </Stack>
              </Paper>

              <Paper variant="outlined" sx={{ p: 1, borderRadius: 1.25 }}>
                <Stack spacing={1} direction="row" alignItems="center" justifyContent="space-between">
                  <Stack spacing={0.5}>
                    <Button component="label" size="small" variant="outlined">
                      Upload Audio (.mp3) (Optional)
                      <input hidden type="file" accept=".mp3,audio/mpeg" onChange={handleAudioChange} />
                    </Button>
                    <Typography variant="caption" color="text.secondary">
                      {audioName || (editingWord?.audio ? 'Current audio' : 'No audio selected')}
                    </Typography>
                    {Boolean(errors.audio_file) && (
                      <Typography variant="caption" color="error.main">
                        {errors.audio_file}
                      </Typography>
                    )}
                  </Stack>
                  <Stack direction="row" spacing={0.25} alignItems="center">
                    <IconButton
                      size="small"
                      disabled={!editingWord?.audio}
                      onClick={() => {
                        if (editingWord?.audio) {
                          window.open(editingWord.audio, '_blank', 'noreferrer');
                        }
                      }}
                      title="Open current audio"
                    >
                      <AudiotrackIcon fontSize="small" />
                    </IconButton>
                    {audioName && (
                      <IconButton size="small" onClick={() => { setWordData('audio_file', null); setAudioName(''); }} title="Clear">
                        <ClearIcon fontSize="small" />
                      </IconButton>
                    )}
                  </Stack>
                </Stack>
              </Paper>
              <TextField label="Channel / Major" value={major} InputProps={{ readOnly: true }} fullWidth size="small" />
            </Stack>
          </DialogContent>
          <DialogActions>
            <Button onClick={() => setWordDialogOpen(false)} disabled={processing} size="small">
              Cancel
            </Button>
            <Button type="submit" variant="contained" disabled={processing} size="small">
              {editingWord ? 'Update' : 'Create'}
            </Button>
          </DialogActions>
        </Box>
      </Dialog>

      <Dialog open={bookDialogOpen} onClose={() => setBookDialogOpen(false)} maxWidth="sm" fullWidth>
        <DialogTitle>{editingBook ? 'Edit Book' : 'Add Book'}</DialogTitle>
        <Box component="form" onSubmit={submitBook}>
          <DialogContent dividers>
            <Stack spacing={1}>
              <TextField
                label="Title"
                value={bookData.title}
                onChange={(e) => setBookData('title', e.target.value)}
                error={Boolean(bookErrors.title)}
                helperText={bookErrors.title}
                fullWidth
                size="small"
              />
              <Autocomplete
                freeSolo
                options={libraryCategories}
                value={bookData.category}
                onChange={(_, value) => setBookData('category', value || '')}
                onInputChange={(_, value) => setBookData('category', value || '')}
                renderInput={(params) => (
                  <TextField
                    {...params}
                    label="Category"
                    error={Boolean(bookErrors.category)}
                    helperText={bookErrors.category}
                    fullWidth
                    size="small"
                  />
                )}
              />

              <Paper variant="outlined" sx={{ p: 1, borderRadius: 1.25 }}>
                <Stack spacing={1} direction="row" alignItems="center" justifyContent="space-between">
                  <Stack spacing={0.5}>
                    <Button component="label" size="small" variant="outlined">
                      Upload PDF
                      <input hidden type="file" accept="application/pdf,.pdf" onChange={handlePdfChange} />
                    </Button>
                    <Typography variant="caption" color="text.secondary">
                      {pdfName || (editingBook?.pdf_url ? 'Current PDF' : 'No PDF selected')}
                    </Typography>
                    {Boolean(bookErrors.pdf_file) && (
                      <Typography variant="caption" color="error.main">
                        {bookErrors.pdf_file}
                      </Typography>
                    )}
                  </Stack>
                  {pdfName && (
                    <IconButton size="small" onClick={() => { setBookData('pdf_file', null); setPdfName(''); }} title="Clear">
                      <ClearIcon fontSize="small" />
                    </IconButton>
                  )}
                </Stack>
              </Paper>

              <Paper variant="outlined" sx={{ p: 1, borderRadius: 1.25 }}>
                <Stack spacing={1}>
                  <Avatar variant="rounded" src={coverPreview} sx={{ width: 56, height: 56, bgcolor: 'action.selected' }}>
                    <ImageIcon fontSize="small" />
                  </Avatar>
                  <Stack direction="row" alignItems="center" justifyContent="space-between">
                    <Button component="label" size="small" variant="outlined">
                      Upload Cover (Optional)
                      <input hidden type="file" accept="image/*" onChange={handleCoverChange} />
                    </Button>
                    {coverName && (
                      <IconButton size="small" onClick={() => { setBookData('cover_file', null); setCoverName(''); setCoverPreview(''); }} title="Clear">
                        <ClearIcon fontSize="small" />
                      </IconButton>
                    )}
                  </Stack>
                  <Typography variant="caption" color="text.secondary">
                    {coverName || (editingBook?.cover_image ? 'Current cover' : 'No cover selected')}
                  </Typography>
                  {Boolean(bookErrors.cover_file) && (
                    <Typography variant="caption" color="error.main">
                      {bookErrors.cover_file}
                    </Typography>
                  )}
                </Stack>
              </Paper>

              <TextField label="Channel / Major" value={major} InputProps={{ readOnly: true }} fullWidth size="small" />
            </Stack>
          </DialogContent>
          <DialogActions>
            <Button onClick={() => setBookDialogOpen(false)} disabled={bookProcessing} size="small">
              Cancel
            </Button>
            <Button type="submit" variant="contained" disabled={bookProcessing} size="small">
              {editingBook ? 'Update' : 'Create'}
            </Button>
          </DialogActions>
        </Box>
      </Dialog>

      <Dialog open={flashcardDeckDialogOpen} onClose={() => setFlashcardDeckDialogOpen(false)} maxWidth="sm" fullWidth>
        <DialogTitle>{editingFlashcardDeck ? 'Edit Deck' : 'Add Deck'}</DialogTitle>
        <Box component="form" onSubmit={submitFlashcardDeck}>
          <DialogContent dividers>
            <Stack spacing={1}>
              <TextField
                label="Title"
                value={flashcardDeckData.title}
                onChange={(e) => setFlashcardDeckData('title', e.target.value)}
                error={Boolean(flashcardDeckErrors.title)}
                helperText={flashcardDeckErrors.title}
                fullWidth
                size="small"
              />
              <TextField
                label="Description (Optional)"
                value={flashcardDeckData.description}
                onChange={(e) => setFlashcardDeckData('description', e.target.value)}
                error={Boolean(flashcardDeckErrors.description)}
                helperText={flashcardDeckErrors.description}
                fullWidth
                size="small"
                multiline
                minRows={3}
              />
              <TextField label="Channel / Major" value={major} InputProps={{ readOnly: true }} fullWidth size="small" />
            </Stack>
          </DialogContent>
          <DialogActions>
            <Button size="small" onClick={() => setFlashcardDeckDialogOpen(false)} disabled={flashcardDeckProcessing}>
              Cancel
            </Button>
            <Button size="small" type="submit" variant="contained" disabled={flashcardDeckProcessing}>
              {editingFlashcardDeck ? 'Update' : 'Create'}
            </Button>
          </DialogActions>
        </Box>
      </Dialog>

      <Dialog open={flashcardBulkDialogOpen} onClose={() => setFlashcardBulkDialogOpen(false)} maxWidth="md" fullWidth>
        <DialogTitle>Bulk Upload Cards (JSON)</DialogTitle>
        <Box component="form" onSubmit={submitFlashcardBulkUpload}>
          <DialogContent dividers>
            <Stack spacing={1.25}>
              <TextField
                select
                label="Deck"
                value={flashcardBulkData.deck_id}
                onChange={(e) => setFlashcardBulkData('deck_id', e.target.value)}
                error={Boolean(flashcardBulkErrors.deck_id)}
                helperText={flashcardBulkErrors.deck_id}
                fullWidth
                size="small"
              >
                {flashcardDecks.map((d) => (
                  <MenuItem key={`bulk-deck-${d.id}`} value={String(d.id)}>
                    {d.title}
                  </MenuItem>
                ))}
              </TextField>

              <Paper variant="outlined" sx={{ p: 1, borderRadius: 1.25 }}>
                <Stack spacing={1} direction="row" alignItems="center" justifyContent="space-between">
                  <Stack spacing={0.5}>
                    <Button component="label" size="small" variant="outlined">
                      Upload JSON File (Optional)
                      <input
                        hidden
                        type="file"
                        accept="application/json,.json,text/plain"
                        onChange={(e) => {
                          const file = e.target.files?.[0] || null;
                          setFlashcardBulkData('cards_file', file);
                          setFlashcardBulkFileName(file ? file.name : '');
                        }}
                      />
                    </Button>
                    <Typography variant="caption" color="text.secondary">
                      {flashcardBulkFileName || 'No file selected'}
                    </Typography>
                    {Boolean(flashcardBulkErrors.cards_file) && (
                      <Typography variant="caption" color="error.main">
                        {flashcardBulkErrors.cards_file}
                      </Typography>
                    )}
                  </Stack>
                  {flashcardBulkFileName && (
                    <IconButton
                      size="small"
                      onClick={() => {
                        setFlashcardBulkData('cards_file', null);
                        setFlashcardBulkFileName('');
                      }}
                      title="Clear"
                    >
                      <ClearIcon fontSize="small" />
                    </IconButton>
                  )}
                </Stack>
              </Paper>

              <Paper variant="outlined" sx={{ p: 1, borderRadius: 1.25 }}>
                <Stack spacing={0.75}>
                  <Typography variant="subtitle2" sx={{ fontWeight: 700 }}>
                    JSON Shape
                  </Typography>
                  <Typography variant="caption" color="text.secondary">
                    Paste an array of cards, or wrap with {"{ \"cards\": [...] }"}. Supported keys:
                    word (required), burmese_translation, ipa, pronunciation_audio, image, parts_of_speech, example_sentences, synonyms, antonyms, relatived.
                  </Typography>
                  <Box
                    component="pre"
                    sx={{
                      m: 0,
                      p: 1,
                      borderRadius: 1,
                      bgcolor: 'action.hover',
                      border: '1px solid',
                      borderColor: 'divider',
                      whiteSpace: 'pre-wrap',
                      wordBreak: 'break-word',
                      fontSize: 12,
                      lineHeight: 1.35,
                      maxHeight: 220,
                      overflow: 'auto',
                    }}
                  >
                    {`{
  "cards": [
    {
      "word": "Apple",
      "burmese_translation": "ပန်းသီး",
      "ipa": "ˈæp.əl",
      "pronunciation_audio": "https://example.com/audio/apple.mp3",
      "image": "https://example.com/images/apple.jpg",
      "parts_of_speech": ["noun"],
      "example_sentences": ["I eat an apple."],
      "synonyms": ["pome"],
      "antonyms": [],
      "relatived": ["fruit"]
    }
  ]
}`}
                  </Box>
                </Stack>
              </Paper>

              <TextField
                label="Cards JSON (array or { cards: [...] })"
                value={flashcardBulkData.cards_json}
                onChange={(e) => setFlashcardBulkData('cards_json', e.target.value)}
                error={Boolean(flashcardBulkErrors.cards_json)}
                helperText={flashcardBulkErrors.cards_json || 'Each item requires at least: { "word": "..." }'}
                fullWidth
                multiline
                minRows={10}
              />

              <TextField label="Channel / Major" value={major} InputProps={{ readOnly: true }} fullWidth size="small" />
            </Stack>
          </DialogContent>
          <DialogActions>
            <Button size="small" onClick={() => setFlashcardBulkDialogOpen(false)} disabled={flashcardBulkProcessing}>
              Cancel
            </Button>
            <Button size="small" type="submit" variant="contained" disabled={flashcardBulkProcessing || !flashcardBulkData.deck_id}>
              {flashcardBulkProcessing ? 'Uploading...' : 'Upload'}
            </Button>
          </DialogActions>
        </Box>
      </Dialog>

      <Dialog open={flashcardCardDialogOpen} onClose={() => setFlashcardCardDialogOpen(false)} maxWidth="md" fullWidth>
        <DialogTitle>{editingFlashcardCard ? 'Edit Card' : 'Add Card'}</DialogTitle>
        <Box component="form" onSubmit={submitFlashcardCard}>
          <DialogContent dividers>
            <Stack spacing={1}>
              <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: '240px 1fr 1fr' }, gap: 1 }}>
                <TextField
                  select
                  label="Deck"
                  value={flashcardCardData.deck_id}
                  onChange={(e) => setFlashcardCardData('deck_id', e.target.value)}
                  error={Boolean(flashcardCardErrors.deck_id)}
                  helperText={flashcardCardErrors.deck_id}
                  fullWidth
                  size="small"
                >
                  {flashcardDecks.map((d) => (
                    <MenuItem key={`deck-opt-${d.id}`} value={String(d.id)}>
                      {d.title}
                    </MenuItem>
                  ))}
                </TextField>
                <TextField
                  label="Word"
                  value={flashcardCardData.word}
                  onChange={(e) => setFlashcardCardData('word', e.target.value)}
                  error={Boolean(flashcardCardErrors.word)}
                  helperText={flashcardCardErrors.word}
                  fullWidth
                  size="small"
                />
                <TextField
                  label="Translation (Optional)"
                  value={flashcardCardData.burmese_translation}
                  onChange={(e) => setFlashcardCardData('burmese_translation', e.target.value)}
                  error={Boolean(flashcardCardErrors.burmese_translation)}
                  helperText={flashcardCardErrors.burmese_translation}
                  fullWidth
                  size="small"
                />
              </Box>

              <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: '1fr 1fr' }, gap: 1 }}>
                <TextField
                  label="IPA (Optional)"
                  value={flashcardCardData.ipa}
                  onChange={(e) => setFlashcardCardData('ipa', e.target.value)}
                  error={Boolean(flashcardCardErrors.ipa)}
                  helperText={flashcardCardErrors.ipa}
                  fullWidth
                  size="small"
                />
                <TextField
                  label="Pronunciation Audio URL (Optional)"
                  value={flashcardCardData.pronunciation_audio}
                  onChange={(e) => setFlashcardCardData('pronunciation_audio', e.target.value)}
                  error={Boolean(flashcardCardErrors.pronunciation_audio)}
                  helperText={flashcardCardErrors.pronunciation_audio}
                  fullWidth
                  size="small"
                />
              </Box>

              <TextField
                label="Image URL (Optional)"
                value={flashcardCardData.image}
                onChange={(e) => setFlashcardCardData('image', e.target.value)}
                error={Boolean(flashcardCardErrors.image)}
                helperText={flashcardCardErrors.image}
                fullWidth
                size="small"
              />

              <Divider>
                <Chip label="Advanced Fields" size="small" variant="outlined" />
              </Divider>

              <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: '1fr 1fr' }, gap: 1 }}>
                <TextField
                  label="Parts of Speech (JSON or list)"
                  value={flashcardCardData.parts_of_speech}
                  onChange={(e) => setFlashcardCardData('parts_of_speech', e.target.value)}
                  error={Boolean(flashcardCardErrors.parts_of_speech)}
                  helperText={flashcardCardErrors.parts_of_speech}
                  fullWidth
                  size="small"
                  multiline
                  minRows={2}
                />
                <TextField
                  label="Synonyms (JSON or list)"
                  value={flashcardCardData.synonyms}
                  onChange={(e) => setFlashcardCardData('synonyms', e.target.value)}
                  error={Boolean(flashcardCardErrors.synonyms)}
                  helperText={flashcardCardErrors.synonyms}
                  fullWidth
                  size="small"
                  multiline
                  minRows={2}
                />
                <TextField
                  label="Antonyms (JSON or list)"
                  value={flashcardCardData.antonyms}
                  onChange={(e) => setFlashcardCardData('antonyms', e.target.value)}
                  error={Boolean(flashcardCardErrors.antonyms)}
                  helperText={flashcardCardErrors.antonyms}
                  fullWidth
                  size="small"
                  multiline
                  minRows={2}
                />
                <TextField
                  label="Related (JSON or list)"
                  value={flashcardCardData.relatived}
                  onChange={(e) => setFlashcardCardData('relatived', e.target.value)}
                  error={Boolean(flashcardCardErrors.relatived)}
                  helperText={flashcardCardErrors.relatived}
                  fullWidth
                  size="small"
                  multiline
                  minRows={2}
                />
              </Box>

              <TextField
                label="Example Sentences (JSON or list)"
                value={flashcardCardData.example_sentences}
                onChange={(e) => setFlashcardCardData('example_sentences', e.target.value)}
                error={Boolean(flashcardCardErrors.example_sentences)}
                helperText={flashcardCardErrors.example_sentences}
                fullWidth
                size="small"
                multiline
                minRows={3}
              />

              <TextField label="Channel / Major" value={major} InputProps={{ readOnly: true }} fullWidth size="small" />
            </Stack>
          </DialogContent>
          <DialogActions>
            <Button size="small" onClick={() => setFlashcardCardDialogOpen(false)} disabled={flashcardCardProcessing}>
              Cancel
            </Button>
            <Button size="small" type="submit" variant="contained" disabled={flashcardCardProcessing}>
              {editingFlashcardCard ? 'Update' : 'Create'}
            </Button>
          </DialogActions>
        </Box>
      </Dialog>

      <Dialog open={speakingTitleDialogOpen} onClose={() => setSpeakingTitleDialogOpen(false)} maxWidth="xs" fullWidth>
        <DialogTitle>{editingSpeakingTitle ? 'Edit Dialogue Title' : 'Add Dialogue Title'}</DialogTitle>
        <Box component="form" onSubmit={submitSpeakingTitle}>
          <DialogContent dividers>
            <Stack spacing={1}>
              <TextField
                label="Title"
                value={speakingTitleData.title}
                onChange={(e) => setSpeakingTitleData('title', e.target.value)}
                error={Boolean(speakingTitleErrors.title)}
                helperText={speakingTitleErrors.title}
                fullWidth
                size="small"
              />
              <TextField label="Channel / Major" value={major} InputProps={{ readOnly: true }} fullWidth size="small" />
            </Stack>
          </DialogContent>
          <DialogActions>
            <Button size="small" onClick={() => setSpeakingTitleDialogOpen(false)} disabled={speakingTitleProcessing}>
              Cancel
            </Button>
            <Button size="small" type="submit" variant="contained" disabled={speakingTitleProcessing}>
              {editingSpeakingTitle ? 'Update' : 'Create'}
            </Button>
          </DialogActions>
        </Box>
      </Dialog>

      <Dialog open={speakingDialogueDialogOpen} onClose={() => setSpeakingDialogueDialogOpen(false)} maxWidth="md" fullWidth>
        <DialogTitle>{editingSpeakingDialogue ? 'Edit Dialogue' : 'Add Dialogue'}</DialogTitle>
        <Box component="form" onSubmit={submitSpeakingDialogue}>
          <DialogContent dividers>
            <Stack spacing={1}>
              <TextField
                label="Title ID"
                value={speakingDialogueData.speaking_dialogue_title_id}
                InputProps={{ readOnly: true }}
                fullWidth
                size="small"
              />
              {Boolean(speakingDialogueErrors.speaking_dialogue_title_id) && (
                <Alert severity="error">{speakingDialogueErrors.speaking_dialogue_title_id}</Alert>
              )}
              <TextField
                label="Sort Order"
                value={speakingDialogueData.sort_order}
                onChange={(e) => setSpeakingDialogueData('sort_order', e.target.value)}
                error={Boolean(speakingDialogueErrors.sort_order)}
                helperText={speakingDialogueErrors.sort_order}
                fullWidth
                size="small"
              />
              <Box sx={{ display: { xs: 'block', md: 'grid' }, gridTemplateColumns: { md: '1fr 1fr' }, gap: 1 }}>
                <Paper variant="outlined" sx={{ p: 1, borderRadius: 1.25 }}>
                  <Stack spacing={1}>
                    <Typography variant="body2" sx={{ fontWeight: 700 }}>
                      Person A
                    </Typography>
                    <TextField
                      label="Text"
                      value={speakingDialogueData.person_a_text}
                      onChange={(e) => setSpeakingDialogueData('person_a_text', e.target.value)}
                      error={Boolean(speakingDialogueErrors.person_a_text)}
                      helperText={speakingDialogueErrors.person_a_text}
                      fullWidth
                      multiline
                      minRows={3}
                    />
                    <TextField
                      label="Translation (Optional)"
                      value={speakingDialogueData.person_a_translation}
                      onChange={(e) => setSpeakingDialogueData('person_a_translation', e.target.value)}
                      error={Boolean(speakingDialogueErrors.person_a_translation)}
                      helperText={speakingDialogueErrors.person_a_translation}
                      fullWidth
                      multiline
                      minRows={2}
                    />
                  </Stack>
                </Paper>
                <Paper variant="outlined" sx={{ p: 1, borderRadius: 1.25 }}>
                  <Stack spacing={1}>
                    <Typography variant="body2" sx={{ fontWeight: 700 }}>
                      Person B
                    </Typography>
                    <TextField
                      label="Text"
                      value={speakingDialogueData.person_b_text}
                      onChange={(e) => setSpeakingDialogueData('person_b_text', e.target.value)}
                      error={Boolean(speakingDialogueErrors.person_b_text)}
                      helperText={speakingDialogueErrors.person_b_text}
                      fullWidth
                      multiline
                      minRows={3}
                    />
                    <TextField
                      label="Translation (Optional)"
                      value={speakingDialogueData.person_b_translation}
                      onChange={(e) => setSpeakingDialogueData('person_b_translation', e.target.value)}
                      error={Boolean(speakingDialogueErrors.person_b_translation)}
                      helperText={speakingDialogueErrors.person_b_translation}
                      fullWidth
                      multiline
                      minRows={2}
                    />
                  </Stack>
                </Paper>
              </Box>
              <TextField label="Channel / Major" value={major} InputProps={{ readOnly: true }} fullWidth size="small" />
            </Stack>
          </DialogContent>
          <DialogActions>
            <Button size="small" onClick={() => setSpeakingDialogueDialogOpen(false)} disabled={speakingDialogueProcessing}>
              Cancel
            </Button>
            <Button size="small" type="submit" variant="contained" disabled={speakingDialogueProcessing}>
              {editingSpeakingDialogue ? 'Update' : 'Create'}
            </Button>
          </DialogActions>
        </Box>
      </Dialog>

      <Menu
        anchorEl={speakingTitleMenuAnchorEl}
        open={Boolean(speakingTitleMenuAnchorEl)}
        onClose={closeSpeakingTitleMenu}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
        transformOrigin={{ vertical: 'top', horizontal: 'right' }}
      >
        <MenuItem
          onClick={() => {
            const row = speakingTitleMenuRow;
            closeSpeakingTitleMenu();
            if (row) {
              openEditSpeakingTitle(row);
            }
          }}
        >
          Edit
        </MenuItem>
        <MenuItem
          onClick={() => {
            const row = speakingTitleMenuRow;
            closeSpeakingTitleMenu();
            if (row) {
              removeSpeakingTitle(row);
            }
          }}
          sx={{ color: 'error.main' }}
        >
          Delete
        </MenuItem>
      </Menu>

      <Menu
        anchorEl={speakingDialogueMenuAnchorEl}
        open={Boolean(speakingDialogueMenuAnchorEl)}
        onClose={closeSpeakingDialogueMenu}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
        transformOrigin={{ vertical: 'top', horizontal: 'right' }}
      >
        <MenuItem
          onClick={() => {
            const row = speakingDialogueMenuRow;
            closeSpeakingDialogueMenu();
            if (row) {
              openEditSpeakingDialogue(row);
            }
          }}
        >
          Edit
        </MenuItem>
        <MenuItem
          onClick={() => {
            const row = speakingDialogueMenuRow;
            closeSpeakingDialogueMenu();
            if (row) {
              removeSpeakingDialogue(row);
            }
          }}
          sx={{ color: 'error.main' }}
        >
          Delete
        </MenuItem>
      </Menu>

      <Menu
        anchorEl={flashcardDeckMenuAnchorEl}
        open={Boolean(flashcardDeckMenuAnchorEl)}
        onClose={closeFlashcardDeckMenu}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
        transformOrigin={{ vertical: 'top', horizontal: 'right' }}
      >
        <MenuItem
          onClick={() => {
            const row = flashcardDeckMenuRow;
            closeFlashcardDeckMenu();
            if (row) {
              openEditFlashcardDeck(row);
            }
          }}
        >
          Edit
        </MenuItem>
        <MenuItem
          onClick={() => {
            const row = flashcardDeckMenuRow;
            closeFlashcardDeckMenu();
            if (row) {
              removeFlashcardDeck(row);
            }
          }}
          sx={{ color: 'error.main' }}
        >
          Delete
        </MenuItem>
      </Menu>

      <Menu
        anchorEl={flashcardCardMenuAnchorEl}
        open={Boolean(flashcardCardMenuAnchorEl)}
        onClose={closeFlashcardCardMenu}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
        transformOrigin={{ vertical: 'top', horizontal: 'right' }}
      >
        <MenuItem
          onClick={() => {
            const row = flashcardCardMenuRow;
            closeFlashcardCardMenu();
            if (row) {
              openEditFlashcardCard(row);
            }
          }}
        >
          Edit
        </MenuItem>
        <MenuItem
          onClick={() => {
            const row = flashcardCardMenuRow;
            closeFlashcardCardMenu();
            if (row) {
              removeFlashcardCard(row);
            }
          }}
          sx={{ color: 'error.main' }}
        >
          Delete
        </MenuItem>
      </Menu>

      <Menu
        anchorEl={wordMenuAnchorEl}
        open={Boolean(wordMenuAnchorEl)}
        onClose={closeWordMenu}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
        transformOrigin={{ vertical: 'top', horizontal: 'right' }}
      >
        <MenuItem
          onClick={() => {
            const row = wordMenuRow;
            closeWordMenu();
            if (row) {
              openEditWord(row);
            }
          }}
        >
          Edit
        </MenuItem>
        <MenuItem
          onClick={() => {
            const row = wordMenuRow;
            closeWordMenu();
            if (row) {
              removeWord(row);
            }
          }}
          sx={{ color: 'error.main' }}
        >
          Delete
        </MenuItem>
      </Menu>

      <Menu
        anchorEl={libraryMenuAnchorEl}
        open={Boolean(libraryMenuAnchorEl)}
        onClose={closeLibraryMenu}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
        transformOrigin={{ vertical: 'top', horizontal: 'right' }}
      >
        <MenuItem
          onClick={() => {
            const row = libraryMenuRow;
            closeLibraryMenu();
            if (row) {
              openEditBook(row);
            }
          }}
        >
          Edit
        </MenuItem>
        <MenuItem
          onClick={() => {
            const row = libraryMenuRow;
            closeLibraryMenu();
            if (row) {
              removeBook(row);
            }
          }}
          sx={{ color: 'error.main' }}
        >
          Delete
        </MenuItem>
      </Menu>

      <Menu
        anchorEl={gameMenuAnchorEl}
        open={Boolean(gameMenuAnchorEl)}
        onClose={closeGameMenu}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
        transformOrigin={{ vertical: 'top', horizontal: 'right' }}
      >
        <MenuItem
          onClick={() => {
            const row = gameMenuRow;
            closeGameMenu();
            if (row) {
              openEditGameWord(row);
            }
          }}
        >
          Edit
        </MenuItem>
        <MenuItem
          onClick={() => {
            const row = gameMenuRow;
            closeGameMenu();
            if (row) {
              removeGameWord(row);
            }
          }}
          sx={{ color: 'error.main' }}
        >
          Delete
        </MenuItem>
      </Menu>

      <Dialog open={gameWordTypeDialogOpen} onClose={() => setGameWordTypeDialogOpen(false)} maxWidth="xs" fullWidth>
        <DialogTitle>Select Game Word Type</DialogTitle>
        <DialogContent dividers>
          <Stack spacing={1.25}>
            <Button
              variant="outlined"
              onClick={() => {
                setGameWordTypeDialogOpen(false);
                openCreateGameWord('word');
              }}
            >
              Display Word
            </Button>
            <Button
              variant="outlined"
              onClick={() => {
                setGameWordTypeDialogOpen(false);
                openCreateGameWord('image');
              }}
            >
              Display Image
            </Button>
            <Button
              variant="outlined"
              onClick={() => {
                setGameWordTypeDialogOpen(false);
                openCreateGameWord('audio');
              }}
            >
              Display Audio
            </Button>
          </Stack>
        </DialogContent>
        <DialogActions>
          <Button size="small" onClick={() => setGameWordTypeDialogOpen(false)}>
            Cancel
          </Button>
        </DialogActions>
      </Dialog>

      <Dialog open={gameWordDialogOpen} onClose={() => setGameWordDialogOpen(false)} maxWidth="sm" fullWidth>
        <DialogTitle>{editingGameWord ? 'Edit Game Word' : 'Add Game Word'}</DialogTitle>
        <Box component="form" onSubmit={submitGameWord}>
          <DialogContent dividers>
            <Stack spacing={1}>
              <TextField label="Type" value={gameWordType} InputProps={{ readOnly: true }} fullWidth size="small" />
              <TextField
                label="Display Word"
                value={gameWordData.display_word}
                onChange={(e) => setGameWordData('display_word', e.target.value)}
                error={Boolean(gameWordErrors.display_word)}
                helperText={gameWordErrors.display_word}
                fullWidth
                size="small"
              />
              <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', sm: 'repeat(3, minmax(0,1fr))' }, gap: 1 }}>
                <TextField
                  label="A"
                  value={gameWordData.a}
                  onChange={(e) => setGameWordData('a', e.target.value)}
                  error={Boolean(gameWordErrors.a)}
                  helperText={gameWordErrors.a}
                  size="small"
                />
                <TextField
                  label="B"
                  value={gameWordData.b}
                  onChange={(e) => setGameWordData('b', e.target.value)}
                  error={Boolean(gameWordErrors.b)}
                  helperText={gameWordErrors.b}
                  size="small"
                />
                <TextField
                  label="C"
                  value={gameWordData.c}
                  onChange={(e) => setGameWordData('c', e.target.value)}
                  error={Boolean(gameWordErrors.c)}
                  helperText={gameWordErrors.c}
                  size="small"
                />
              </Box>
              <TextField
                select
                label="Answer"
                value={gameWordData.ans}
                onChange={(e) => setGameWordData('ans', e.target.value)}
                error={Boolean(gameWordErrors.ans)}
                helperText={gameWordErrors.ans}
                fullWidth
                size="small"
              >
                <MenuItem value="a">A</MenuItem>
                <MenuItem value="b">B</MenuItem>
                <MenuItem value="c">C</MenuItem>
              </TextField>

              {gameWordType === 'image' && (
                <Paper variant="outlined" sx={{ p: 1, borderRadius: 1.25 }}>
                  <Stack spacing={1}>
                    <Box sx={{ borderRadius: 1, overflow: 'hidden', bgcolor: 'action.hover', aspectRatio: '16 / 9', maxWidth: 320 }}>
                      {gameImagePreview && (
                        <Box component="img" src={gameImagePreview} alt="Game" sx={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                      )}
                      {!gameImagePreview && (
                        <Stack alignItems="center" justifyContent="center" sx={{ width: '100%', height: '100%' }}>
                          <ImageIcon color="action" />
                        </Stack>
                      )}
                    </Box>
                    <Stack direction="row" alignItems="center" justifyContent="space-between">
                      <Button component="label" size="small" variant="outlined">
                        Upload & Crop Image (16:9)
                        <input hidden type="file" accept="image/*" onChange={handleGameImageChange} />
                      </Button>
                      {(gameImageName || gameImagePreview) && (
                        <IconButton size="small" onClick={() => { setGameWordData('display_image_file', null); setGameImageName(''); setGameImagePreview(''); }} title="Clear">
                          <ClearIcon fontSize="small" />
                        </IconButton>
                      )}
                    </Stack>
                    <Typography variant="caption" color="text.secondary">
                      {gameImageName || (editingGameWord?.display_image ? 'Current image' : 'No image selected')}
                    </Typography>
                    {Boolean(gameWordErrors.display_image_file) && (
                      <Typography variant="caption" color="error.main">
                        {gameWordErrors.display_image_file}
                      </Typography>
                    )}
                  </Stack>
                </Paper>
              )}

              {gameWordType === 'audio' && (
                <Paper variant="outlined" sx={{ p: 1, borderRadius: 1.25 }}>
                  <Stack spacing={1} direction="row" alignItems="center" justifyContent="space-between">
                    <Stack spacing={0.5}>
                      <Button component="label" size="small" variant="outlined">
                        Upload Audio (.mp3)
                        <input hidden type="file" accept=".mp3,audio/mpeg" onChange={handleGameAudioChange} />
                      </Button>
                      <Typography variant="caption" color="text.secondary">
                        {gameAudioName || (editingGameWord?.display_audio ? 'Current audio' : 'No audio selected')}
                      </Typography>
                      {Boolean(gameWordErrors.display_audio_file) && (
                        <Typography variant="caption" color="error.main">
                          {gameWordErrors.display_audio_file}
                        </Typography>
                      )}
                    </Stack>
                    <Stack direction="row" spacing={0.25} alignItems="center">
                      <IconButton
                        size="small"
                        disabled={!editingGameWord?.display_audio}
                        onClick={() => {
                          if (editingGameWord?.display_audio) {
                            window.open(editingGameWord.display_audio, '_blank', 'noreferrer');
                          }
                        }}
                        title="Open current audio"
                      >
                        <AudiotrackIcon fontSize="small" />
                      </IconButton>
                      {gameAudioName && (
                        <IconButton size="small" onClick={() => { setGameWordData('display_audio_file', null); setGameAudioName(''); }} title="Clear">
                          <ClearIcon fontSize="small" />
                        </IconButton>
                      )}
                    </Stack>
                  </Stack>
                </Paper>
              )}

              <TextField label="Channel / Major" value={major} InputProps={{ readOnly: true }} fullWidth size="small" />
            </Stack>
          </DialogContent>
          <DialogActions>
            <Button size="small" onClick={() => setGameWordDialogOpen(false)} disabled={gameWordProcessing}>
              Cancel
            </Button>
            <Button size="small" type="submit" variant="contained" disabled={gameWordProcessing}>
              {editingGameWord ? 'Update' : 'Create'}
            </Button>
          </DialogActions>
        </Box>
      </Dialog>

      <Snackbar open={Boolean(flash?.success)} autoHideDuration={3000} anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}>
        <Alert severity="success" variant="filled">
          {flash?.success || 'Action completed successfully.'}
        </Alert>
      </Snackbar>

      <ImageCropper
        open={thumbCropperOpen}
        image={thumbCropTempImage}
        aspect={16 / 9}
        title="Crop Word Image (16:9)"
        onCancel={() => {
          setThumbCropperOpen(false);
          setThumbCropTempImage(null);
        }}
        onCropComplete={(blob) => {
          if (!blob) {
            setThumbCropperOpen(false);
            setThumbCropTempImage(null);
            return;
          }
          const file = new File([blob], `word_thumb_${Date.now()}.jpg`, { type: 'image/jpeg' });
          setWordData('thumb_file', file);
          setThumbName(file.name);
          const previewUrl = URL.createObjectURL(file);
          setThumbPreview((prev) => {
            if (prev && prev.startsWith('blob:')) {
              URL.revokeObjectURL(prev);
            }
            return previewUrl;
          });
          setThumbCropperOpen(false);
          setThumbCropTempImage(null);
        }}
      />

      <ImageCropper
        open={gameImageCropperOpen}
        image={gameImageCropTempImage}
        aspect={16 / 9}
        title="Crop Game Image (16:9)"
        onCancel={() => {
          setGameImageCropperOpen(false);
          setGameImageCropTempImage(null);
        }}
        onCropComplete={(blob) => {
          if (!blob) {
            setGameImageCropperOpen(false);
            setGameImageCropTempImage(null);
            return;
          }
          const file = new File([blob], `game_word_${Date.now()}.jpg`, { type: 'image/jpeg' });
          setGameWordData('display_image_file', file);
          setGameImageName(file.name);
          const previewUrl = URL.createObjectURL(file);
          setGameImagePreview((prev) => {
            if (prev && prev.startsWith('blob:')) {
              URL.revokeObjectURL(prev);
            }
            return previewUrl;
          });
          setGameImageCropperOpen(false);
          setGameImageCropTempImage(null);
        }}
      />
    </Box>
  );
}

ResourceManagementWorkspace.layout = (page) => <AdminLayout children={page} />;
