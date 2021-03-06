/*  Moment Video Server - High performance media server
    Copyright (C) 2013 Dmitry Shatrov
    e-mail: shatrov@gmail.com

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/


#ifndef MOMENT_FFMPEG__NVR_CLEANER__H__
#define MOMENT_FFMPEG__NVR_CLEANER__H__


#include <moment/libmoment.h>
#include <moment-ffmpeg/rec_path_config.h>


namespace MomentFFmpeg {

using namespace M;
using namespace Moment;

class NvrCleaner : public Object
{
private:
    RecpathConfig * m_recpathConfig;
    mt_const StRef<String> stream_name;
    mt_const Time max_age_sec;

    mt_const DataDepRef<Timers> timers;
    Timers::TimerKey timer_key;

    void doRemoveFiles (ConstMemory filename, Vfs * vfs);

    static void cleanupTimerTick (void *_self);

public:

    NvrCleaner();
    ~NvrCleaner();

    mt_const void init (Timers      * mt_nonnull timers,
                        RecpathConfig * pRecpathConfig,
                        ConstMemory  stream_name,
                        Time         max_age_sec,
                        Time         clean_interval_sec);
};

}


#endif /* MOMENT_FFMPEG__NVR_CLEANER__H__ */

