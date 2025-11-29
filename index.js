import React, { useState, useEffect } from 'react';
import { Star, BarChart3, Users, MessageSquare, TrendingUp, Download, Filter, Calendar, Menu, X, Lock, User, FileText, Home, Settings, LogOut, Eye, EyeOff } from 'lucide-react';

// Department data with icons and descriptions
const departments = [
  {
    id: 1,
    name: "Spirit & Life: THE WORD",
    shortName: "The Word",
    icon: "📖",
    description: "Biblical teaching and spiritual nourishment through God's Word",
    color: "bg-blue-500"
  },
  {
    id: 2,
    name: "Spirit & Power Ministry (SPM): MUSIC",
    shortName: "Music Ministry",
    icon: "🎵",
    description: "Worship and praise through music and songs",
    color: "bg-purple-500"
  },
  {
    id: 3,
    name: "The Fire Place: PRAYER",
    shortName: "Prayer Ministry",
    icon: "🙏",
    description: "Intercession and prayer support for the church",
    color: "bg-red-500"
  },
  {
    id: 4,
    name: "Be Well: HEALTH",
    shortName: "Health Ministry",
    icon: "💚",
    description: "Health and wellness programs for members",
    color: "bg-green-500"
  },
  {
    id: 5,
    name: "Sanctuary Keepers: SANITATION",
    shortName: "Sanitation",
    icon: "🧹",
    description: "Maintaining cleanliness and hygiene in God's house",
    color: "bg-teal-500"
  },
  {
    id: 6,
    name: "Environment: SECURITY & SAFETY",
    shortName: "Security",
    icon: "🛡️",
    description: "Ensuring safety and security for all members",
    color: "bg-orange-500"
  },
  {
    id: 7,
    name: "Services & Programs",
    shortName: "Services",
    icon: "📅",
    description: "Coordinating church services and special programs",
    color: "bg-indigo-500"
  },
  {
    id: 8,
    name: "Dominion Membership Connect (DMC): EVANGELISM",
    shortName: "DMC",
    icon: "🌍",
    description: "Evangelism and follow-up for new members",
    color: "bg-yellow-500"
  },
  {
    id: 9,
    name: "Training & Development (T&D)",
    shortName: "T&D",
    icon: "🎓",
    description: "Sunday School, discipleship and development programs",
    color: "bg-pink-500"
  },
  {
    id: 10,
    name: "Dominion Impact Centre (DIC)",
    shortName: "DIC",
    icon: "🤝",
    description: "Ushering, protocol, empowerment and welfare",
    color: "bg-cyan-500"
  },
  {
    id: 11,
    name: "Family Affairs & House Fellowship",
    shortName: "Family Affairs",
    icon: "👨‍👩‍👧‍👦",
    description: "Family programs and house fellowship coordination",
    color: "bg-rose-500"
  },
  {
    id: 12,
    name: "Dominion Air Force (DAF): MEDIA",
    shortName: "Media",
    icon: "📹",
    description: "Media production and broadcast services",
    color: "bg-violet-500"
  },
  {
    id: 13,
    name: "Sound & Light: TECHNICAL",
    shortName: "Technical",
    icon: "🔊",
    description: "Audio, lighting and technical support",
    color: "bg-amber-500"
  },
  {
    id: 14,
    name: "IT, Software & Electronics",
    shortName: "IT",
    icon: "💻",
    description: "Technology infrastructure and support",
    color: "bg-sky-500"
  },
  {
    id: 15,
    name: "Maintenance & Electrical",
    shortName: "Maintenance",
    icon: "🔧",
    description: "Building maintenance and electrical services",
    color: "bg-gray-500"
  },
  {
    id: 16,
    name: "Creative Arts & Talents",
    shortName: "Creative Arts",
    icon: "🎭",
    description: "Drama, dancing, spoken word and other talents",
    color: "bg-fuchsia-500"
  },
  {
    id: 17,
    name: "Sports, Entertainment & Outreach",
    shortName: "Sports",
    icon: "⚽",
    description: "Sports activities and community outreach",
    color: "bg-lime-500"
  },
  {
    id: 18,
    name: "Junior Church: DTCE",
    shortName: "Junior Church",
    icon: "👶",
    description: "Teens and children education programs",
    color: "bg-emerald-500"
  },
  {
    id: 19,
    name: "General Services: FACILITY & ADMINISTRATION",
    shortName: "Administration",
    icon: "🏢",
    description: "Facility management and administrative services",
    color: "bg-slate-500"
  }
];

const ChurchReviewSystem = () => {
  const [currentView, setCurrentView] = useState('login');
  const [userRole, setUserRole] = useState(null);
  const [selectedDepartment, setSelectedDepartment] = useState(null);
  const [showPassword, setShowPassword] = useState(false);
  const [menuOpen, setMenuOpen] = useState(false);
  
  // Login state
  const [loginData, setLoginData] = useState({ username: '', password: '' });
  
  // Review state
  const [reviews, setReviews] = useState([]);
  const [currentRating, setCurrentRating] = useState(0);
  const [currentComment, setCurrentComment] = useState('');
  const [generalRating, setGeneralRating] = useState(0);
  const [generalComment, setGeneralComment] = useState('');
  
  // Admin filters
  const [filterDept, setFilterDept] = useState('all');
  const [filterDate, setFilterDate] = useState('all');
  
  // Load reviews from storage
  useEffect(() => {
    const stored = window.storage ? null : localStorage.getItem('churchReviews');
    if (stored) {
      setReviews(JSON.parse(stored));
    } else {
      loadStoredReviews();
    }
  }, []);
  
  const loadStoredReviews = async () => {
    try {
      const result = await window.storage.get('church-reviews');
      if (result) {
        setReviews(JSON.parse(result.value));
      }
    } catch (error) {
      console.log('No stored reviews found');
    }
  };
  
  const saveReviews = async (newReviews) => {
    setReviews(newReviews);
    if (window.storage) {
      try {
        await window.storage.set('church-reviews', JSON.stringify(newReviews));
      } catch (error) {
        console.error('Storage error:', error);
      }
    } else {
      localStorage.setItem('churchReviews', JSON.stringify(newReviews));
    }
  };

  const handleLogin = (e) => {
    e.preventDefault();
    if (loginData.username === 'admin' && loginData.password === 'admin123') {
      setUserRole('admin');
      setCurrentView('admin-dashboard');
    } else if (loginData.username && loginData.password) {
      setUserRole('member');
      setCurrentView('member-home');
    } else {
      alert('Please enter valid credentials');
    }
  };

  const handleLogout = () => {
    setUserRole(null);
    setCurrentView('login');
    setLoginData({ username: '', password: '' });
  };

  const submitReview = async () => {
    if (currentRating === 0) {
      alert('Please select a rating');
      return;
    }
    
    const newReview = {
      id: Date.now(),
      departmentId: selectedDepartment,
      departmentName: departments.find(d => d.id === selectedDepartment)?.name,
      rating: currentRating,
      comment: currentComment,
      date: new Date().toISOString(),
      type: 'department'
    };
    
    await saveReviews([...reviews, newReview]);
    setCurrentRating(0);
    setCurrentComment('');
    setSelectedDepartment(null);
    setCurrentView('member-home');
    alert('Thank you for your feedback!');
  };

  const submitGeneralReview = async () => {
    if (generalRating === 0) {
      alert('Please select a rating');
      return;
    }
    
    const newReview = {
      id: Date.now(),
      departmentId: 0,
      departmentName: 'General Service',
      rating: generalRating,
      comment: generalComment,
      date: new Date().toISOString(),
      type: 'general'
    };
    
    await saveReviews([...reviews, newReview]);
    setGeneralRating(0);
    setGeneralComment('');
    setCurrentView('member-home');
    alert('Thank you for your feedback!');
  };

  const StarRating = ({ rating, setRating, size = 32 }) => {
    return (
      <div className="flex gap-2">
        {[1, 2, 3, 4, 5].map((star) => (
          <button
            key={star}
            onClick={() => setRating(star)}
            className="transition-transform hover:scale-110"
          >
            <Star
              size={size}
              fill={star <= rating ? '#fbbf24' : 'none'}
              stroke={star <= rating ? '#fbbf24' : '#d1d5db'}
              strokeWidth={2}
            />
          </button>
        ))}
      </div>
    );
  };

  const calculateStats = () => {
    const deptStats = departments.map(dept => {
      const deptReviews = reviews.filter(r => r.departmentId === dept.id);
      const avg = deptReviews.length > 0
        ? (deptReviews.reduce((sum, r) => sum + r.rating, 0) / deptReviews.length).toFixed(1)
        : 0;
      return { ...dept, avgRating: parseFloat(avg), totalReviews: deptReviews.length };
    });

    const generalReviews = reviews.filter(r => r.type === 'general');
    const generalAvg = generalReviews.length > 0
      ? (generalReviews.reduce((sum, r) => sum + r.rating, 0) / generalReviews.length).toFixed(1)
      : 0;

    return { deptStats, generalAvg, generalCount: generalReviews.length };
  };

  // Login Page
  if (currentView === 'login') {
    return (
      <div className="min-h-screen bg-gradient-to-br from-blue-50 to-purple-50 flex items-center justify-center p-4">
        <div className="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md">
          <div className="text-center mb-8">
            <div className="text-6xl mb-4">⛪</div>
            <h1 className="text-3xl font-bold text-gray-800 mb-2">Church Review System</h1>
            <p className="text-gray-600">Performance & Service Rating Platform</p>
          </div>
          
          <form onSubmit={handleLogin} className="space-y-6">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                <User size={18} className="inline mr-2" />
                Username
              </label>
              <input
                type="text"
                value={loginData.username}
                onChange={(e) => setLoginData({...loginData, username: e.target.value})}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Enter username"
                required
              />
            </div>
            
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                <Lock size={18} className="inline mr-2" />
                Password
              </label>
              <div className="relative">
                <input
                  type={showPassword ? "text" : "password"}
                  value={loginData.password}
                  onChange={(e) => setLoginData({...loginData, password: e.target.value})}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Enter password"
                  required
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500"
                >
                  {showPassword ? <EyeOff size={20} /> : <Eye size={20} />}
                </button>
              </div>
            </div>
            
            <button
              type="submit"
              className="w-full bg-gradient-to-r from-blue-500 to-purple-600 text-white py-3 rounded-lg font-semibold hover:from-blue-600 hover:to-purple-700 transition-all transform hover:scale-105"
            >
              Sign In
            </button>
          </form>
          
          <div className="mt-6 p-4 bg-blue-50 rounded-lg text-sm">
            <p className="font-semibold text-blue-900 mb-2">Demo Credentials:</p>
            <p className="text-blue-700">Admin: admin / admin123</p>
            <p className="text-blue-700">Member: any username & password</p>
          </div>
        </div>
      </div>
    );
  }

  // Member Home Page
  if (currentView === 'member-home' && userRole === 'member') {
    return (
      <div className="min-h-screen bg-gradient-to-br from-blue-50 to-purple-50">
        <nav className="bg-white shadow-lg">
          <div className="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <div className="flex items-center gap-3">
              <span className="text-3xl">⛪</span>
              <h1 className="text-2xl font-bold text-gray-800">Rate Our Departments</h1>
            </div>
            <button
              onClick={handleLogout}
              className="flex items-center gap-2 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors"
            >
              <LogOut size={18} />
              Logout
            </button>
          </div>
        </nav>

        <div className="max-w-7xl mx-auto px-4 py-8">
          <div className="mb-8 text-center">
            <h2 className="text-3xl font-bold text-gray-800 mb-2">Share Your Experience</h2>
            <p className="text-gray-600">Your feedback helps us serve you better</p>
          </div>

          {/* General Service Rating Card */}
          <div className="mb-8">
            <div
              onClick={() => setCurrentView('general-rating')}
              className="bg-gradient-to-r from-purple-500 to-pink-500 rounded-2xl shadow-xl p-8 cursor-pointer transform hover:scale-105 transition-all"
            >
              <div className="flex items-center justify-between text-white">
                <div>
                  <h3 className="text-2xl font-bold mb-2">Rate Overall Church Service</h3>
                  <p className="text-purple-100">Share your general experience with our services</p>
                </div>
                <div className="text-6xl">⭐</div>
              </div>
            </div>
          </div>

          {/* Department Grid */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {departments.map((dept) => (
              <div
                key={dept.id}
                onClick={() => {
                  setSelectedDepartment(dept.id);
                  setCurrentView('rating-form');
                }}
                className="bg-white rounded-xl shadow-lg overflow-hidden cursor-pointer transform hover:scale-105 transition-all hover:shadow-2xl"
              >
                <div className={`${dept.color} h-32 flex items-center justify-center`}>
                  <span className="text-6xl">{dept.icon}</span>
                </div>
                <div className="p-6">
                  <h3 className="text-lg font-bold text-gray-800 mb-2">{dept.shortName}</h3>
                  <p className="text-sm text-gray-600">{dept.description}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    );
  }

  // Rating Form for Departments
  if (currentView === 'rating-form' && selectedDepartment) {
    const dept = departments.find(d => d.id === selectedDepartment);
    return (
      <div className="min-h-screen bg-gradient-to-br from-blue-50 to-purple-50 p-4">
        <div className="max-w-2xl mx-auto">
          <button
            onClick={() => {
              setCurrentView('member-home');
              setSelectedDepartment(null);
              setCurrentRating(0);
              setCurrentComment('');
            }}
            className="mb-6 flex items-center gap-2 text-gray-600 hover:text-gray-800"
          >
            ← Back to Departments
          </button>

          <div className="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div className={`${dept.color} p-8 text-white text-center`}>
              <div className="text-6xl mb-4">{dept.icon}</div>
              <h2 className="text-3xl font-bold mb-2">{dept.name}</h2>
              <p className="text-white text-opacity-90">{dept.description}</p>
            </div>

            <div className="p-8">
              <div className="mb-8">
                <label className="block text-lg font-semibold text-gray-800 mb-4">
                  Rate this department (1-5 stars)
                </label>
                <div className="flex justify-center">
                  <StarRating rating={currentRating} setRating={setCurrentRating} size={48} />
                </div>
                <p className="text-center mt-2 text-gray-600">
                  {currentRating === 0 && 'Select a rating'}
                  {currentRating === 1 && 'Poor'}
                  {currentRating === 2 && 'Fair'}
                  {currentRating === 3 && 'Good'}
                  {currentRating === 4 && 'Very Good'}
                  {currentRating === 5 && 'Excellent'}
                </p>
              </div>

              <div className="mb-8">
                <label className="block text-lg font-semibold text-gray-800 mb-4">
                  Additional Comments (Optional)
                </label>
                <textarea
                  value={currentComment}
                  onChange={(e) => setCurrentComment(e.target.value)}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                  rows="5"
                  placeholder="Share your thoughts, suggestions, or experiences..."
                />
              </div>

              <button
                onClick={submitReview}
                className="w-full bg-gradient-to-r from-blue-500 to-purple-600 text-white py-4 rounded-lg font-semibold text-lg hover:from-blue-600 hover:to-purple-700 transition-all transform hover:scale-105"
              >
                Submit Review
              </button>
            </div>
          </div>
        </div>
      </div>
    );
  }

  // General Service Rating Form
  if (currentView === 'general-rating') {
    return (
      <div className="min-h-screen bg-gradient-to-br from-blue-50 to-purple-50 p-4">
        <div className="max-w-2xl mx-auto">
          <button
            onClick={() => {
              setCurrentView('member-home');
              setGeneralRating(0);
              setGeneralComment('');
            }}
            className="mb-6 flex items-center gap-2 text-gray-600 hover:text-gray-800"
          >
            ← Back to Home
          </button>

          <div className="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div className="bg-gradient-to-r from-purple-500 to-pink-500 p-8 text-white text-center">
              <div className="text-6xl mb-4">⭐</div>
              <h2 className="text-3xl font-bold mb-2">General Church Service Rating</h2>
              <p className="text-white text-opacity-90">Rate your overall church experience</p>
            </div>

            <div className="p-8">
              <div className="mb-8">
                <label className="block text-lg font-semibold text-gray-800 mb-4">
                  Overall Service Rating (1-5 stars)
                </label>
                <div className="flex justify-center">
                  <StarRating rating={generalRating} setRating={setGeneralRating} size={48} />
                </div>
                <p className="text-center mt-2 text-gray-600">
                  {generalRating === 0 && 'Select a rating'}
                  {generalRating === 1 && 'Poor'}
                  {generalRating === 2 && 'Fair'}
                  {generalRating === 3 && 'Good'}
                  {generalRating === 4 && 'Very Good'}
                  {generalRating === 5 && 'Excellent'}
                </p>
              </div>

              <div className="mb-8">
                <label className="block text-lg font-semibold text-gray-800 mb-4">
                  Your Feedback (Optional)
                </label>
                <textarea
                  value={generalComment}
                  onChange={(e) => setGeneralComment(e.target.value)}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent resize-none"
                  rows="5"
                  placeholder="Share your overall experience with our church services..."
                />
              </div>

              <button
                onClick={submitGeneralReview}
                className="w-full bg-gradient-to-r from-purple-500 to-pink-600 text-white py-4 rounded-lg font-semibold text-lg hover:from-purple-600 hover:to-pink-700 transition-all transform hover:scale-105"
              >
                Submit Feedback
              </button>
            </div>
          </div>
        </div>
      </div>
    );
  }

  // Admin Dashboard
  if (currentView === 'admin-dashboard' && userRole === 'admin') {
    const { deptStats, generalAvg, generalCount } = calculateStats();
    const filteredReviews = reviews.filter(r => {
      if (filterDept !== 'all' && r.departmentId !== parseInt(filterDept)) return false;
      if (filterDate === 'today') {
        const today = new Date().toDateString();
        return new Date(r.date).toDateString() === today;
      }
      if (filterDate === 'week') {
        const weekAgo = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000);
        return new Date(r.date) >= weekAgo;
      }
      if (filterDate === 'month') {
        const monthAgo = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000);
        return new Date(r.date) >= monthAgo;
      }
      return true;
    });

    const totalReviews = reviews.length;
    const avgAllDepts = deptStats.length > 0
      ? (deptStats.reduce((sum, d) => sum + d.avgRating, 0) / deptStats.filter(d => d.totalReviews > 0).length || 0).toFixed(1)
      : 0;

    return (
      <div className="min-h-screen bg-gray-50">
        {/* Top Navigation */}
        <nav className="bg-white shadow-lg">
          <div className="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <div className="flex items-center gap-3">
              <span className="text-3xl">⛪</span>
              <div>
                <h1 className="text-2xl font-bold text-gray-800">Admin Dashboard</h1>
                <p className="text-sm text-gray-600">Performance Analytics & Reviews</p>
              </div>
            </div>
            <button
              onClick={handleLogout}
              className="flex items-center gap-2 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors"
            >
              <LogOut size={18} />
              Logout
            </button>
          </div>
        </nav>

        <div className="max-w-7xl mx-auto px-4 py-8">
          {/* Stats Overview */}
          <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div className="bg-white rounded-xl shadow-lg p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-gray-600 text-sm font-medium">Total Reviews</p>
                  <p className="text-3xl font-bold text-gray-800 mt-2">{totalReviews}</p>
                </div>
                <div className="bg-blue-100 p-3 rounded-full">
                  <MessageSquare size={24} className="text-blue-600" />
                </div>
              </div>
            </div>

            <div className="bg-white rounded-xl shadow-lg p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-gray-600 text-sm font-medium">Avg Department Rating</p>
                  <p className="text-3xl font-bold text-gray-800 mt-2">{avgAllDepts}</p>
                </div>
                <div className="bg-yellow-100 p-3 rounded-full">
                  <Star size={24} className="text-yellow-600" />
                </div>
              </div>
            </div>

            <div className="bg-white rounded-xl shadow-lg p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-gray-600 text-sm font-medium">General Service Rating</p>
                  <p className="text-3xl font-bold text-gray-800 mt-2">{generalAvg}</p>
                </div>
                <div className="bg-purple-100 p-3 rounded-full">
                  <TrendingUp size={24} className="text-purple-600" />
                </div>
              </div>
            </div>

            <div className="bg-white rounded-xl shadow-lg p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-gray-600 text-sm font-medium">Active Departments</p>
                  <p className="text-3xl font-bold text-gray-800 mt-2">{departments.length}</p>
                </div>
                <div className="bg-green-100 p-3 rounded-full">
                  <BarChart3 size={24} className="text-green-600" />
                </div>
              </div>
            </div>
          </div>

          {/* Filters */}
          <div className="bg-white rounded-xl shadow-lg p-6 mb-8">
            <div className="flex items-center gap-4 flex-wrap">
              <div className="flex items-center gap-2">
                <Filter size={20} className="text-gray-600" />
                <span className="font-semibold text-gray-800">Filters:</span>
              </div>
              
              <select
                value={filterDept}
                onChange={(e) => setFilterDept(e.target.value)}
                className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              >
                <option value="all">All Departments</option>
                <option value="0">General Service</option>
                {departments.map(d => (
                  <option key={d.id} value={d.id}>{d.shortName}</option>
                ))}
              </select>

              <select
                value={filterDate}
                onChange={(e) => setFilterDate(e.target.value)}
                className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              >
                <option value="all">All Time</option>
                <option value="today">Today</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
              </select>

              <button
                onClick={() => alert('Export functionality - Connect to backend')}
                className="ml-auto flex items-center gap-2 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors"
              >
                <Download size={18} />
                Export Report
              </button>
            </div>
          </div>

          {/* Department Performance Grid */}
          <div className="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h2 className="text-2xl font-bold text-gray-800 mb-6">Department Performance</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {deptStats.map(dept => (
                <div key={dept.id} className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                  <div className="flex items-center gap-3 mb-3">
                    <span className="text-3xl">{dept.icon}</span>
                    <div>
                      <h3 className="font-semibold text-gray-800">{dept.shortName}</h3>
                      <p className="text-sm text-gray-600">{dept.totalReviews} reviews</p>
                    </div>
                  </div>
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-1">
                      {[1,2,3,4,5].map(star => (
                        <Star
                          key={star}
                          size={16}
                          fill={star <= dept.avgRating ? '#fbbf24' : 'none'}
                          stroke={star <= dept.avgRating ? '#fbbf24' : '#d1d5db'}
                        />
                      ))}
                    </div>
                    <span className="text-xl font-bold text-gray-800">{dept.avgRating}</span>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Recent Reviews */}
          <div className="bg-white rounded-xl shadow-lg p-6">
            <h2 className="text-2xl font-bold text-gray-800 mb-6">Recent Reviews & Comments</h2>
            <div className="space-y-4">
              {filteredReviews.length === 0 ? (
                <p className="text-center text-gray-500 py-8">No reviews found</p>
              ) : (
                filteredReviews.slice(0, 20).reverse().map(review => (
                  <div key={review.id} className="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                    <div className="flex items-start justify-between mb-2">
                      <div>
                        <h4 className="font-semibold text-gray-800">{review.departmentName}</h4>
                        <p className="text-sm text-gray-500">
                          {new Date(review.date).toLocaleDateString()} at {new Date(review.date).toLocaleTimeString()}
                        </p>
                      </div>
                      <div className="flex items-center gap-1">
                        {[1,2,3,4,5].map(star => (
                          <Star
                            key={star}
                            size={16}
                            fill={star <= review.rating ? '#fbbf24' : 'none'}
                            stroke={star <= review.rating ? '#fbbf24' : '#d1d5db'}
                          />
                        ))}
                        <span className="ml-2 font-semibold text-gray-800">{review.rating}</span>
                      </div>
                    </div>
                    {review.comment && (
                      <p className="text-gray-700 mt-2 italic">"{review.comment}"</p>
                    )}
                  </div>
                ))
              )}
            </div>
          </div>
        </div>
      </div>
    );
  }

  return null;
};

export default ChurchReviewSystem;

// 