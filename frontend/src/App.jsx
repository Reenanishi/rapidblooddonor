import { useEffect, useMemo, useState } from 'react';

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000';

const starterProfile = {
  id: 'donor-sf-a-pos',
  name: '',
  type: 'individual',
  bloodGroup: 'A',
  rhd: 'positive',
  zipcode: '94103',
  latitude: '37.7749',
  longitude: '-122.4194',
  email: '',
  phone: '',
  preferences: { email: true, phone: false, sms: true },
  availability: 'Available today',
};

const signupProfile = {
  id: 'donor-new-o-pos',
  name: 'New Donor',
  type: 'individual',
  bloodGroup: 'O',
  rhd: 'positive',
  zipcode: '94103',
  latitude: '37.7749',
  longitude: '-122.4194',
  email: 'new.donor@example.com',
  phone: '+14155550000',
  preferences: { email: true, phone: false, sms: true },
  availability: 'Available today',
};

const initialFilters = {
  zipcode: '94103',
  latitude: '',
  longitude: '',
  radiusKm: '25',
  bloodGroup: '',
  rhd: '',
  type: '',
};

const tabs = [
  { id: 'search', label: 'Search' },
  { id: 'track', label: 'Track' },
  { id: 'profile', label: 'Profile' },
];

function buildQuery(filters) {
  const query = new URLSearchParams();
  Object.entries(filters).forEach(([key, value]) => {
    if (value !== '') query.set(key, value);
  });
  return query.toString();
}

function channelLabel(channel) {
  return channel === 'sms' ? 'SMS' : channel.charAt(0).toUpperCase() + channel.slice(1);
}

function asApiProfile(profile) {
  return {
    ...profile,
    latitude: Number(profile.latitude),
    longitude: Number(profile.longitude),
  };
}

function profileFromApi(user) {
  return {
    ...user,
    latitude: String(user.latitude),
    longitude: String(user.longitude),
  };
}

export default function App() {
  const [activeTab, setActiveTab] = useState('search');
  const [authMode, setAuthMode] = useState('signin');
  const [signinId, setSigninId] = useState(starterProfile.id);
  const [profile, setProfile] = useState(starterProfile);
  const [draftProfile, setDraftProfile] = useState(starterProfile);
  const [filters, setFilters] = useState(initialFilters);
  const [matches, setMatches] = useState([]);
  const [requests, setRequests] = useState([]);
  const [mode, setMode] = useState('zipcode');
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');

  const requestStats = useMemo(() => {
    return requests.reduce(
      (stats, request) => ({ ...stats, [request.status]: (stats[request.status] || 0) + 1 }),
      { pending: 0, accepted: 0, declined: 0 },
    );
  }, [requests]);

  const acceptedRequests = requests.filter((request) => request.status === 'accepted').length;

  async function api(path, options) {
    const response = await fetch(`${API_BASE}${path}`, options);
    const data = await response.json();
    if (!response.ok) {
      throw new Error(data.error || data.errors?.join(', ') || 'Request failed');
    }
    return data;
  }

  async function signIn(event) {
    event.preventDefault();
    setMessage('');
    try {
      const data = await api(`/api/users/${signinId}`);
      const nextProfile = profileFromApi(data.user);
      setProfile(nextProfile);
      setDraftProfile(nextProfile);
      setMessage(`Signed in as ${nextProfile.name}.`);
      loadRequests(nextProfile.id);
    } catch (error) {
      setMessage(error.message);
    }
  }

  async function signUp(event) {
    event.preventDefault();
    setMessage('');
    try {
      const data = await api('/api/users', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(asApiProfile(draftProfile)),
      });
      const nextProfile = profileFromApi(data.user);
      setProfile(nextProfile);
      setDraftProfile(nextProfile);
      setSigninId(nextProfile.id);
      setAuthMode('signin');
      setMessage('Account created and signed in.');
      loadRequests(nextProfile.id);
    } catch (error) {
      setMessage(error.message);
    }
  }

  async function saveProfile(event) {
    event.preventDefault();
    setMessage('');
    try {
      const data = await api(`/api/users/${profile.id}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(asApiProfile(draftProfile)),
      });
      const nextProfile = profileFromApi(data.user);
      setProfile(nextProfile);
      setDraftProfile(nextProfile);
      setMessage('Profile and contact preferences updated.');
    } catch (error) {
      setMessage(error.message);
    }
  }

  async function search(nextFilters = filters) {
    setLoading(true);
    setMessage('');
    try {
      const data = await api(`/api/search?${buildQuery(nextFilters)}`);
      setMatches(data.matches || []);
      setMode(data.mode || 'all');
    } catch (error) {
      setMessage('Unable to reach the Python backend. Start it with python3 backend/app.py.');
    } finally {
      setLoading(false);
    }
  }

  async function loadRequests(userId = profile.id) {
    try {
      const data = await api(`/api/requests?userId=${encodeURIComponent(userId)}`);
      setRequests(data.requests || []);
    } catch (error) {
      setMessage(error.message);
    }
  }

  async function notifyMatch(match, channel) {
    setMessage('');
    try {
      const data = await api('/api/requests', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          requesterId: profile.id,
          recipientId: match.id,
          bloodType: match.bloodType,
          channel,
          note: `Urgent ${match.bloodType} request from ${profile.name || profile.id}`,
        }),
      });
      setRequests((current) => [data.request, ...current]);
      setActiveTab('track');
      setMessage(`Notification sent to ${match.name}.`);
    } catch (error) {
      setMessage(error.message);
    }
  }

  async function changeRequestStatus(requestId, status) {
    setMessage('');
    try {
      const data = await api(`/api/requests/${requestId}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ status }),
      });
      setRequests((current) => current.map((request) => (request.id === requestId ? data.request : request)));
    } catch (error) {
      setMessage(error.message);
    }
  }

  function updateFilter(key, value) {
    setFilters((current) => {
      const next = { ...current, [key]: value };
      if (key === 'zipcode' && value !== '') {
        next.latitude = '';
        next.longitude = '';
      }
      if ((key === 'latitude' || key === 'longitude') && value !== '') {
        next.zipcode = '';
      }
      return next;
    });
  }

  function updateDraft(key, value) {
    setDraftProfile((current) => ({ ...current, [key]: value }));
  }

  function updatePreference(channel, value) {
    setDraftProfile((current) => ({
      ...current,
      preferences: { ...current.preferences, [channel]: value },
    }));
  }

  useEffect(() => {
    search(initialFilters);
    signIn({ preventDefault: () => {} });
  }, []);

  return (
    <main className="command-shell">
      <aside className="sidebar">
        <div className="brand-lockup">
          <span className="brand-mark">R</span>
          <div>
            <p className="eyebrow">Rapid Blood Donor</p>
            <h1>Regional response desk</h1>
          </div>
        </div>

        <div className="account-panel">
          <div className="segmented">
            <button className={authMode === 'signin' ? 'active' : ''} type="button" onClick={() => setAuthMode('signin')}>
              Sign in
            </button>
            <button className={authMode === 'signup' ? 'active' : ''} type="button" onClick={() => {
              setAuthMode('signup');
              setDraftProfile(signupProfile);
            }}>
              Sign up
            </button>
          </div>

          {authMode === 'signin' ? (
            <form className="compact-form" onSubmit={signIn}>
              <label>
                User ID
                <input value={signinId} onChange={(event) => setSigninId(event.target.value)} />
              </label>
              <button className="primary-button" type="submit">Enter dashboard</button>
            </form>
          ) : (
            <ProfileForm
              profile={draftProfile}
              onChange={updateDraft}
              onPreferenceChange={updatePreference}
              onSubmit={signUp}
              submitLabel="Create account"
            />
          )}
        </div>

        <nav className="app-nav" aria-label="Workspace views">
          {tabs.map((tab) => (
            <button
              className={activeTab === tab.id ? 'active' : ''}
              key={tab.id}
              type="button"
              onClick={() => {
                setActiveTab(tab.id);
                if (tab.id === 'track') loadRequests();
              }}
            >
              {tab.label}
            </button>
          ))}
        </nav>
      </aside>

      <section className="workspace">
        <header className="workspace-header">
          <div>
            <p className="eyebrow">Signed in as {profile.name || profile.id}</p>
            <h2>Coordinate donors, hospitals, and critical requests</h2>
          </div>
          <div className="stat-strip">
            <Metric label="Matches" value={matches.length} />
            <Metric label="Pending" value={requestStats.pending} />
            <Metric label="Accepted" value={acceptedRequests} />
          </div>
        </header>

        {message && <p className="notice">{message}</p>}

        {activeTab === 'search' && (
          <SearchView
            filters={filters}
            matches={matches}
            mode={mode}
            loading={loading}
            onFilterChange={updateFilter}
            onSearch={() => search()}
            onNotify={notifyMatch}
          />
        )}

        {activeTab === 'track' && (
          <TrackView
            currentUserId={profile.id}
            requests={requests}
            onRefresh={() => loadRequests()}
            onStatusChange={changeRequestStatus}
          />
        )}

        {activeTab === 'profile' && (
          <section className="profile-layout">
            <div className="section-heading">
              <p className="eyebrow">Profile controls</p>
              <h2>Change location, blood profile, and contact privacy</h2>
            </div>
            <ProfileForm
              profile={draftProfile}
              onChange={updateDraft}
              onPreferenceChange={updatePreference}
              onSubmit={saveProfile}
              submitLabel="Save changes"
            />
          </section>
        )}
      </section>
    </main>
  );
}

function Metric({ label, value }) {
  return (
    <div className="metric">
      <span>{label}</span>
      <strong>{value}</strong>
    </div>
  );
}

function ProfileForm({ profile, onChange, onPreferenceChange, onSubmit, submitLabel }) {
  return (
    <form className="profile-form" onSubmit={onSubmit}>
      <div className="form-grid">
        <label>
          User ID
          <input value={profile.id} onChange={(event) => onChange('id', event.target.value)} />
        </label>
        <label>
          Name
          <input value={profile.name} onChange={(event) => onChange('name', event.target.value)} />
        </label>
        <label>
          Account type
          <select value={profile.type} onChange={(event) => onChange('type', event.target.value)}>
            <option value="individual">Individual donor</option>
            <option value="hospital">Hospital</option>
          </select>
        </label>
        <label>
          Blood group
          <select value={profile.bloodGroup} onChange={(event) => onChange('bloodGroup', event.target.value)}>
            <option value="A">A</option>
            <option value="B">B</option>
            <option value="AB">AB</option>
            <option value="O">O</option>
          </select>
        </label>
        <label>
          RhD
          <select value={profile.rhd} onChange={(event) => onChange('rhd', event.target.value)}>
            <option value="positive">Positive</option>
            <option value="negative">Negative</option>
          </select>
        </label>
        <label>
          Zip code
          <input value={profile.zipcode} onChange={(event) => onChange('zipcode', event.target.value)} />
        </label>
        <label>
          Latitude
          <input value={profile.latitude} onChange={(event) => onChange('latitude', event.target.value)} />
        </label>
        <label>
          Longitude
          <input value={profile.longitude} onChange={(event) => onChange('longitude', event.target.value)} />
        </label>
        <label>
          Email
          <input value={profile.email} onChange={(event) => onChange('email', event.target.value)} />
        </label>
        <label>
          Phone
          <input value={profile.phone} onChange={(event) => onChange('phone', event.target.value)} />
        </label>
      </div>
      <label>
        Availability
        <input value={profile.availability} onChange={(event) => onChange('availability', event.target.value)} />
      </label>
      <div className="preference-row">
        {['email', 'sms', 'phone'].map((channel) => (
          <label className="toggle-line" key={channel}>
            <input
              checked={Boolean(profile.preferences[channel])}
              type="checkbox"
              onChange={(event) => onPreferenceChange(channel, event.target.checked)}
            />
            Allow {channelLabel(channel)}
          </label>
        ))}
      </div>
      <button className="primary-button" type="submit">{submitLabel}</button>
    </form>
  );
}

function SearchView({ filters, matches, mode, loading, onFilterChange, onSearch, onNotify }) {
  const activeFilters = [
    filters.zipcode && `Zip ${filters.zipcode}`,
    filters.latitude && filters.longitude && `${filters.radiusKm} km radius`,
    filters.bloodGroup && `Group ${filters.bloodGroup}`,
    filters.rhd && `RhD ${filters.rhd}`,
    filters.type && filters.type,
  ].filter(Boolean);

  return (
    <section className="search-layout">
      <form
        className="search-card"
        onSubmit={(event) => {
          event.preventDefault();
          onSearch();
        }}
      >
        <div className="section-heading">
          <p className="eyebrow">Advanced search</p>
          <h2>Find regional matches by privacy-safe geography</h2>
        </div>
        <div className="form-grid">
          <label>
            Zip code
            <input value={filters.zipcode} onChange={(event) => onFilterChange('zipcode', event.target.value)} />
          </label>
          <label>
            Latitude
            <input value={filters.latitude} onChange={(event) => onFilterChange('latitude', event.target.value)} />
          </label>
          <label>
            Longitude
            <input value={filters.longitude} onChange={(event) => onFilterChange('longitude', event.target.value)} />
          </label>
          <label>
            Radius
            <select value={filters.radiusKm} onChange={(event) => onFilterChange('radiusKm', event.target.value)}>
              <option value="5">5 km</option>
              <option value="10">10 km</option>
              <option value="25">25 km</option>
              <option value="50">50 km</option>
              <option value="100">100 km</option>
            </select>
          </label>
          <label>
            Blood group
            <select value={filters.bloodGroup} onChange={(event) => onFilterChange('bloodGroup', event.target.value)}>
              <option value="">Any</option>
              <option value="A">A</option>
              <option value="B">B</option>
              <option value="AB">AB</option>
              <option value="O">O</option>
            </select>
          </label>
          <label>
            RhD
            <select value={filters.rhd} onChange={(event) => onFilterChange('rhd', event.target.value)}>
              <option value="">Any</option>
              <option value="positive">Positive</option>
              <option value="negative">Negative</option>
            </select>
          </label>
          <label>
            Match type
            <select value={filters.type} onChange={(event) => onFilterChange('type', event.target.value)}>
              <option value="">Individuals and hospitals</option>
              <option value="individual">Individuals</option>
              <option value="hospital">Hospitals</option>
            </select>
          </label>
        </div>
        <button className="primary-button" type="submit" disabled={loading}>
          {loading ? 'Searching...' : 'Search matches'}
        </button>
      </form>

      <div className="results-column">
        <div className="results-toolbar">
          <div>
            <p className="eyebrow">Matched by {mode}</p>
            <h2>{matches.length} available match{matches.length === 1 ? '' : 'es'}</h2>
          </div>
          <div className="chips">
            {activeFilters.map((filter) => <span key={filter}>{filter}</span>)}
          </div>
        </div>
        <div className="results-grid">
          {matches.map((match) => (
            <article className="match-card" key={match.id}>
              <div className="match-header">
                <div>
                  <p className="match-type">{match.type === 'hospital' ? 'Hospital partner' : 'Individual donor'}</p>
                  <h3>{match.name}</h3>
                </div>
                <span className="blood-badge">{match.bloodType}</span>
              </div>
              <dl>
                <div><dt>Status</dt><dd>{match.availability}</dd></div>
                <div><dt>Location</dt><dd>{match.distanceKm === null ? `Zip ${match.zipcode}` : `${match.distanceKm} km away`}</dd></div>
              </dl>
              <div className="contact-row">
                {['email', 'sms', 'phone'].map((channel) =>
                  match.contactOptions[channel] ? (
                    <button key={channel} type="button" onClick={() => onNotify(match, channel)}>
                      Notify by {channelLabel(channel)}
                    </button>
                  ) : (
                    <span key={channel} className="private-channel">{channelLabel(channel)} private</span>
                  ),
                )}
              </div>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}

function TrackView({ currentUserId, requests, onRefresh, onStatusChange }) {
  return (
    <section className="track-panel">
      <div className="section-heading with-action">
        <div>
          <p className="eyebrow">Request tracking</p>
          <h2>See who was notified and who accepted</h2>
        </div>
        <button className="secondary-button" type="button" onClick={onRefresh}>Refresh</button>
      </div>
      <div className="request-list">
        {requests.length === 0 && <p className="empty-state">No notifications have been sent yet.</p>}
        {requests.map((request) => {
          const incoming = request.recipientId === currentUserId;
          return (
            <article className="request-row" key={request.id}>
              <div>
                <span className={`status-pill ${request.status}`}>{request.status}</span>
                <h3>{incoming ? request.requesterName : request.recipientName}</h3>
                <p>
                  {request.bloodType} request via {channelLabel(request.channel)} · {incoming ? 'Incoming' : 'Sent'}
                </p>
              </div>
              <div className="request-actions">
                {incoming && request.status === 'pending' ? (
                  <>
                    <button type="button" onClick={() => onStatusChange(request.id, 'accepted')}>Accept</button>
                    <button type="button" onClick={() => onStatusChange(request.id, 'declined')}>Decline</button>
                  </>
                ) : (
                  <span>{new Date(request.updatedAt).toLocaleString()}</span>
                )}
              </div>
            </article>
          );
        })}
      </div>
    </section>
  );
}
