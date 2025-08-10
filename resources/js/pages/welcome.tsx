import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;

    const appName = import.meta.env.VITE_APP_NAME || 'Redis AI Hackathon';

    return (
        <>
            <Head title="Welcome">

            </Head>
            <main>
                <header className="bg-white shadow-md p-2 h-16 flex items-center fixed w-full">
                    <h1 className="text-2xl font-bold">{appName}</h1>
                </header>
                <div className="flex py-16 fixed w-full h-full top-0 right-0">
                    <div className="basis-2/3 p-2 h-full overflow-x-hidden overflow-y-scroll">
                        test
                    </div>
                    <div className="basis-1/3 bg-gray-700 p-2 text-white">
                        test
                    </div>
                </div>
                <div className="fixed h-16 w-full left-0 bottom-0 shadow-md flex items-center p-2 border-t justify-between gap-4">
                    <textarea name="test" id="test" className="h-12 border-gray-300 border rounded-sm basis-4/5"></textarea>
                    <button className="bg-blue-500 text-white h-12 w-32 rounded-sm basis-1/5 cursor-pointer hover:bg-blue-600" >Send</button>
                </div>
            </main>
        </>
    );
}
