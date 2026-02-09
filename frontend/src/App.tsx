/**
 * App.tsx - Main application router
 * NytroAI design: React Router with auth context
 * Navigation: Training / Framework / Settings three-tier structure
 * Uses React.lazy() for code splitting — reduces initial bundle by ~60%
 */
import { lazy, Suspense } from "react";
import { Toaster } from "@/components/ui/sonner";
import { TooltipProvider } from "@/components/ui/tooltip";
import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";
import ErrorBoundary from "./components/ErrorBoundary";
import { ThemeProvider } from "./contexts/ThemeContext";
import { AuthProvider, useAuth } from "./contexts/AuthContext";
import { ProtectedRoute } from "./components/ProtectedRoute";

// Lazy-loaded pages for code splitting
const Landing = lazy(() => import("./pages/Landing"));
const Dashboard = lazy(() => import("./pages/Dashboard"));
const Students = lazy(() => import("./pages/Students"));
const Courses = lazy(() => import("./pages/Courses"));
const Assessments = lazy(() => import("./pages/Assessments"));
const Enrolments = lazy(() => import("./pages/Enrolments"));
const Companies = lazy(() => import("./pages/Companies"));
const Reports = lazy(() => import("./pages/Reports"));
const UserManagement = lazy(() => import("./pages/UserManagement"));
const Settings = lazy(() => import("./pages/Settings"));
const Profile = lazy(() => import("./pages/Profile"));
const ResetPassword = lazy(() => import("./pages/ResetPassword"));
const ToDo = lazy(() => import("./pages/ToDo"));
const CalendarPage = lazy(() => import("./pages/CalendarPage"));
const AllRecords = lazy(() => import("./pages/AllRecords"));
const Intakes = lazy(() => import("./pages/Intakes"));
const Organisation = lazy(() => import("./pages/Organisation"));
const Trainers = lazy(() => import("./pages/Trainers"));
const Leaders = lazy(() => import("./pages/Leaders"));
const Resources = lazy(() => import("./pages/Resources"));
const Communications = lazy(() => import("./pages/Communications"));
const Automation = lazy(() => import("./pages/Automation"));
const DataTypes = lazy(() => import("./pages/DataTypes"));
const MyCourses = lazy(() => import("./pages/MyCourses"));

function PageLoader() {
  return (
    <div className="min-h-[60vh] flex items-center justify-center">
      <div className="flex flex-col items-center gap-3">
        <div className="w-8 h-8 border-3 border-[#dbeafe] border-t-[#3b82f6] rounded-full animate-spin" />
        <p className="text-[#94a3b8] text-xs">Loading...</p>
      </div>
    </div>
  );
}

function AppRoutes() {
  const { isAuthenticated, isLoading } = useAuth();

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-[#f8f9fb]">
        <div className="flex flex-col items-center gap-4">
          <div className="w-12 h-12 border-4 border-[#dbeafe] border-t-[#3b82f6] rounded-full animate-spin" />
          <p className="text-[#64748b] text-sm">Loading NytroLMS...</p>
        </div>
      </div>
    );
  }

  return (
    <Suspense fallback={<PageLoader />}>
    <Routes>
      {/* Public route - Landing/Login */}
      <Route
        path="/"
        element={isAuthenticated ? <Navigate to="/dashboard" replace /> : <Landing />}
      />

      {/* Training section */}
      <Route path="/dashboard" element={<ProtectedRoute><Dashboard /></ProtectedRoute>} />
      <Route path="/my-courses" element={<ProtectedRoute><MyCourses /></ProtectedRoute>} />
      <Route path="/my-courses/:courseId" element={<ProtectedRoute><MyCourses /></ProtectedRoute>} />
      <Route path="/training/todo" element={<ProtectedRoute><ToDo /></ProtectedRoute>} />
      <Route path="/training/calendar" element={<ProtectedRoute><CalendarPage /></ProtectedRoute>} />
      <Route path="/training/records" element={<ProtectedRoute><AllRecords /></ProtectedRoute>} />
      <Route path="/training/intakes" element={<ProtectedRoute><Intakes /></ProtectedRoute>} />
      <Route path="/students" element={<ProtectedRoute><Students /></ProtectedRoute>} />
      <Route path="/assessments" element={<ProtectedRoute><Assessments /></ProtectedRoute>} />
      <Route path="/enrolments" element={<ProtectedRoute><Enrolments /></ProtectedRoute>} />
      <Route path="/reports" element={<ProtectedRoute><Reports /></ProtectedRoute>} />

      {/* Framework section */}
      <Route path="/courses" element={<ProtectedRoute><Courses /></ProtectedRoute>} />
      <Route path="/framework/organisation" element={<ProtectedRoute><Organisation /></ProtectedRoute>} />
      <Route path="/companies" element={<ProtectedRoute><Companies /></ProtectedRoute>} />
      <Route path="/framework/trainers" element={<ProtectedRoute><Trainers /></ProtectedRoute>} />
      <Route path="/framework/leaders" element={<ProtectedRoute><Leaders /></ProtectedRoute>} />
      <Route path="/framework/resources" element={<ProtectedRoute><Resources /></ProtectedRoute>} />

      {/* Settings section */}
      <Route path="/settings" element={<ProtectedRoute><Settings /></ProtectedRoute>} />
      <Route path="/user-management" element={<ProtectedRoute><UserManagement /></ProtectedRoute>} />
      <Route path="/settings/communications" element={<ProtectedRoute><Communications /></ProtectedRoute>} />
      <Route path="/settings/automation" element={<ProtectedRoute><Automation /></ProtectedRoute>} />
      <Route path="/settings/data-types" element={<ProtectedRoute><DataTypes /></ProtectedRoute>} />

      {/* Profile */}
      <Route path="/profile" element={<ProtectedRoute><Profile /></ProtectedRoute>} />

      {/* Password reset route */}
      <Route path="/reset-password" element={<ResetPassword />} />

      {/* Catch-all redirect */}
      <Route
        path="*"
        element={isAuthenticated ? <Navigate to="/dashboard" replace /> : <Navigate to="/" replace />}
      />
    </Routes>
    </Suspense>
  );
}

function App() {
  return (
    <ErrorBoundary>
      <ThemeProvider defaultTheme="light">
        <BrowserRouter>
          <AuthProvider>
            <TooltipProvider>
              <Toaster />
              <AppRoutes />
            </TooltipProvider>
          </AuthProvider>
        </BrowserRouter>
      </ThemeProvider>
    </ErrorBoundary>
  );
}

export default App;
